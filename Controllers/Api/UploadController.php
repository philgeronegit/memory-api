
<?php
class UploadController extends BaseController
{
  private const MAX_FILE_SIZE = 5242880; // 5MB
  private const UPLOAD_DIR = __DIR__ . '/../../uploads/';
  private const ALLOWED_MIME_TYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf'
  ];
  private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

  public function __construct()
  {
    parent::__construct(new NoteModel());
  }

  public function addAction(): void
  {
    $logger = SecurityLogger::getInstance();
    $currentUser = $this->getAuthenticatedUser();

    try {
      $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      $uri = explode('/', $uri);
      $userId = $uri[3] ?? null;

      if (empty($userId) || !is_numeric($userId)) {
        $logger->logSuspiciousActivity('INVALID_USER_ID', [
          'provided_user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array("status" => "error", "message" => "Valid user ID is required."),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      // Verify user is uploading to their own directory or has admin permissions
      if ($currentUser->id_user != $userId && !in_array($currentUser->role, ['admin', 'projectManager'])) {
        $logger->logAuthorizationFailure(
          'file_upload',
          'upload_to_user_directory',
          $currentUser->id_user,
          $currentUser->role
        );
        $this->sendOutput(
          array("status" => "error", "message" => "Unauthorized: Cannot upload files for another user."),
          array('Content-Type: application/json', 'HTTP/1.1 403 Forbidden')
        );
        return;
      }

      if (!isset($_FILES['file'])) {
        $this->sendOutput(
          array("status" => "error", "message" => "No file was uploaded."),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      $file = $_FILES['file'];

      // Check for upload errors
      if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = $this->getUploadErrorMessage($file['error']);
        $logger->logSuspiciousActivity('FILE_UPLOAD_ERROR', [
          'error_code' => $file['error'],
          'error_message' => $errorMessage,
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array("status" => "error", "message" => "Upload error: " . $errorMessage),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      // Validate file size
      if ($file['size'] > self::MAX_FILE_SIZE) {
        $logger->logSuspiciousActivity('FILE_SIZE_EXCEEDED', [
          'file_size' => $file['size'],
          'max_size' => self::MAX_FILE_SIZE,
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array("status" => "error", "message" => "File size exceeds maximum allowed size of 5MB."),
          array('Content-Type: application/json', 'HTTP/1.1 413 Payload Too Large')
        );
        return;
      }

      // Validate file type using MIME type check (not just extension)
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_file($finfo, $file['tmp_name']);
      finfo_close($finfo);

      if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
        $logger->logSuspiciousActivity('INVALID_FILE_TYPE', [
          'mime_type' => $mimeType,
          'filename' => $file['name'],
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array("status" => "error", "message" => "Invalid file type. Only JPG, PNG, GIF, and PDF files are allowed."),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      // Additional extension validation
      $originalFileName = basename($file['name']);
      $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

      if (!in_array($fileExtension, self::ALLOWED_EXTENSIONS)) {
        $logger->logSuspiciousActivity('INVALID_FILE_EXTENSION', [
          'extension' => $fileExtension,
          'filename' => $originalFileName,
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array("status" => "error", "message" => "Invalid file extension."),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      // Generate secure filename to prevent path traversal and overwrites
      $safeFileName = $this->generateSecureFileName($originalFileName, $fileExtension);

      // Create user directory with secure permissions
      $userDir = self::UPLOAD_DIR . $userId . '/';
      if (!file_exists($userDir)) {
        if (!mkdir($userDir, 0750, true)) {
          $logger->logSuspiciousActivity('DIRECTORY_CREATION_FAILED', [
            'directory' => $userDir,
            'user_id' => $userId
          ]);
          $this->sendOutput(
            array("status" => "error", "message" => "Failed to create upload directory."),
            array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
          );
          return;
        }
      }

      $targetFilePath = $userDir . $safeFileName;

      // Move uploaded file with validation
      if (!is_uploaded_file($file['tmp_name'])) {
        $logger->logSuspiciousActivity('FILE_UPLOAD_TAMPERING', [
          'temp_file' => $file['tmp_name'],
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array("status" => "error", "message" => "File upload validation failed."),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      if (!move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        $logger->logSuspiciousActivity('FILE_MOVE_FAILED', [
          'target_path' => $targetFilePath,
          'user_id' => $userId
        ]);
        $this->sendOutput(
          array("status" => "error", "message" => "Failed to save uploaded file."),
          array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
        );
        return;
      }

      // Set secure file permissions
      chmod($targetFilePath, 0640);

      // Log successful upload
      $logger->logInfo('FILE_UPLOADED', [
        'user_id' => $userId,
        'filename' => $safeFileName,
        'original_filename' => $originalFileName,
        'file_size' => $file['size'],
        'mime_type' => $mimeType,
        'uploaded_by' => $currentUser->id_user
      ]);

      $this->sendOutput(
        array(
          "status" => "success",
          "message" => "File uploaded successfully.",
          "filename" => $safeFileName,
          "size" => $file['size']
        ),
        array('Content-Type: application/json', 'HTTP/1.1 200 OK')
      );

    } catch (Exception $e) {
      $logger->logError('FILE_UPLOAD_EXCEPTION', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
      ]);
      $this->sendOutput(
        array("status" => "error", "message" => "An unexpected error occurred during file upload."),
        array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
      );
    }
  }

  public function listAction($args = [])
  {
    $logger = SecurityLogger::getInstance();
    $currentUser = $this->getAuthenticatedUser();

    try {
      $userId = $args['id'] ?? null;

      if (empty($userId) || !is_numeric($userId)) {
        $this->sendOutput(
          array('error' => "Valid user ID is required."),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      // Verify user is accessing their own files or has admin permissions
      if ($currentUser->id_user != $userId && !in_array($currentUser->role, ['admin', 'projectManager'])) {
        $logger->logAuthorizationFailure(
          'file_listing',
          'list_user_files',
          $currentUser->id_user,
          $currentUser->role
        );
        $this->sendOutput(
          array('error' => "Unauthorized: Cannot access another user's files."),
          array('Content-Type: application/json', 'HTTP/1.1 403 Forbidden')
        );
        return;
      }

      $userDir = self::UPLOAD_DIR . $userId . '/';

      if (!file_exists($userDir)) {
        $this->sendOutput(
          array('error' => "No files found for this user."),
          array('Content-Type: application/json', 'HTTP/1.1 404 Not Found')
        );
        return;
      }

      // Prevent directory traversal
      $realUserDir = realpath($userDir);
      $realUploadDir = realpath(self::UPLOAD_DIR);

      if ($realUserDir === false || strpos($realUserDir, $realUploadDir) !== 0) {
        $logger->logSuspiciousActivity('DIRECTORY_TRAVERSAL_ATTEMPT', [
          'user_id' => $userId,
          'requested_path' => $userDir,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array('error' => "Invalid directory access."),
          array('Content-Type: application/json', 'HTTP/1.1 403 Forbidden')
        );
        return;
      }

      $files = array_diff(scandir($realUserDir), array('.', '..'));
      $fileList = [];

      foreach ($files as $file) {
        $filePath = $realUserDir . DIRECTORY_SEPARATOR . $file;

        // Only list files, not subdirectories
        if (is_file($filePath)) {
          // Determine file type
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $mimeType = finfo_file($finfo, $filePath);
          finfo_close($finfo);

          // Use view endpoint for images, download endpoint for PDFs
          $isImage = in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']);
          $url = $isImage
            ? 'view/' . $userId . '/' . urlencode($file)
            : 'upload/' . $userId . '/' . urlencode($file);

          $fileList[] = array(
            'name' => $file,
            'size' => filesize($filePath),
            'type' => $mimeType,
            'url' => $url,
            'modified' => date('Y-m-d H:i:s', filemtime($filePath))
          );
        }
      }

      $logger->logInfo('FILE_LISTING_ACCESSED', [
        'user_id' => $userId,
        'accessed_by' => $currentUser->id_user,
        'file_count' => count($fileList)
      ]);

      $this->sendOutput(
        $fileList,
        array('Content-Type: application/json', 'HTTP/1.1 200 OK')
      );

    } catch (Exception $e) {
      $logger->logError('FILE_LISTING_EXCEPTION', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? 'unknown'
      ]);
      $this->sendOutput(
        array('error' => "An unexpected error occurred."),
        array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
      );
    }
  }

  public function viewAction($args = [])
  {
    $logger = SecurityLogger::getInstance();

    try {
      $userId = $args['id'] ?? null;
      $filename = $args['filename'] ?? null;

      if (empty($userId) || !is_numeric($userId)) {
        http_response_code(400);
        echo 'Invalid user ID';
        return;
      }

      if (empty($filename)) {
        http_response_code(400);
        echo 'Filename required';
        return;
      }

      // Sanitize filename to prevent path traversal
      $safeFilename = basename($filename);
      if ($safeFilename !== $filename || strpos($filename, '..') !== false) {
        $logger->logSuspiciousActivity('FILE_VIEW_PATH_TRAVERSAL', [
          'filename' => $filename,
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        http_response_code(400);
        echo 'Invalid filename';
        return;
      }

      $userDir = self::UPLOAD_DIR . $userId . '/';
      $filePath = $userDir . $safeFilename;

      // Prevent directory traversal
      $realFilePath = realpath($filePath);
      $realUploadDir = realpath(self::UPLOAD_DIR);

      if ($realFilePath === false || strpos($realFilePath, $realUploadDir) !== 0) {
        $logger->logSuspiciousActivity('FILE_VIEW_TRAVERSAL_ATTEMPT', [
          'filename' => $filename,
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        http_response_code(403);
        echo 'Invalid file access';
        return;
      }

      if (!file_exists($realFilePath) || !is_file($realFilePath)) {
        http_response_code(404);
        echo 'File not found';
        return;
      }

      // Verify file type - only allow images for inline viewing
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_file($finfo, $realFilePath);
      finfo_close($finfo);

      $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
      if (!in_array($mimeType, $allowedImageTypes)) {
        $logger->logSuspiciousActivity('FILE_VIEW_INVALID_TYPE', [
          'mime_type' => $mimeType,
          'filename' => $safeFilename,
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        http_response_code(403);
        echo 'Only image files can be viewed inline';
        return;
      }

      $logger->logInfo('FILE_VIEWED', [
        'user_id' => $userId,
        'filename' => $safeFilename,
        'file_size' => filesize($realFilePath),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
      ]);

      // Clear any output buffers to prevent corruption of binary data
      while (ob_get_level()) {
        ob_end_clean();
      }

      // Set headers for inline viewing
      header('Content-Type: ' . $mimeType);
      header('Content-Length: ' . filesize($realFilePath));
      header('Content-Disposition: inline; filename="' . addslashes($safeFilename) . '"');
      header('Cache-Control: public, max-age=86400');
      header('X-Content-Type-Options: nosniff');

      readfile($realFilePath);
      exit;

    } catch (Exception $e) {
      $logger->logError('FILE_VIEW_EXCEPTION', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? 'unknown',
        'filename' => $filename ?? 'unknown'
      ]);
      http_response_code(500);
      echo 'An unexpected error occurred';
    }
  }

  public function downloadAction($args = [])
  {
    $logger = SecurityLogger::getInstance();
    $currentUser = $this->getAuthenticatedUser();

    try {
      $userId = $args['id'] ?? null;
      $filename = $args['filename'] ?? null;

      if (empty($userId) || !is_numeric($userId)) {
        $this->sendOutput(
          array('error' => "Valid user ID is required."),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      if (empty($filename)) {
        $this->sendOutput(
          array('error' => "Filename is required."),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      // Verify user is accessing their own files or has admin permissions
      if ($currentUser->id_user != $userId && !in_array($currentUser->role, ['admin', 'projectManager'])) {
        $logger->logAuthorizationFailure(
          'file_download',
          'download_user_file',
          $currentUser->id_user,
          $currentUser->role
        );
        $this->sendOutput(
          array('error' => "Unauthorized: Cannot access another user's files."),
          array('Content-Type: application/json', 'HTTP/1.1 403 Forbidden')
        );
        return;
      }

      // Sanitize filename to prevent path traversal
      $safeFilename = basename($filename);
      if ($safeFilename !== $filename || strpos($filename, '..') !== false) {
        $logger->logSuspiciousActivity('FILE_DOWNLOAD_PATH_TRAVERSAL', [
          'filename' => $filename,
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array('error' => "Invalid filename."),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
        return;
      }

      $userDir = self::UPLOAD_DIR . $userId . '/';
      $filePath = $userDir . $safeFilename;

      // Prevent directory traversal
      $realFilePath = realpath($filePath);
      $realUploadDir = realpath(self::UPLOAD_DIR);

      if ($realFilePath === false || strpos($realFilePath, $realUploadDir) !== 0) {
        $logger->logSuspiciousActivity('FILE_DOWNLOAD_TRAVERSAL_ATTEMPT', [
          'filename' => $filename,
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array('error' => "Invalid file access."),
          array('Content-Type: application/json', 'HTTP/1.1 403 Forbidden')
        );
        return;
      }

      if (!file_exists($realFilePath) || !is_file($realFilePath)) {
        $this->sendOutput(
          array('error' => "File not found."),
          array('Content-Type: application/json', 'HTTP/1.1 404 Not Found')
        );
        return;
      }

      // Verify file type before serving
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_file($finfo, $realFilePath);
      finfo_close($finfo);

      if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
        $logger->logSuspiciousActivity('SUSPICIOUS_FILE_DOWNLOAD', [
          'mime_type' => $mimeType,
          'filename' => $safeFilename,
          'user_id' => $userId,
          'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->sendOutput(
          array('error' => "File type not allowed for download."),
          array('Content-Type: application/json', 'HTTP/1.1 403 Forbidden')
        );
        return;
      }

      $logger->logInfo('FILE_DOWNLOADED', [
        'user_id' => $userId,
        'filename' => $safeFilename,
        'downloaded_by' => $currentUser->id_user,
        'file_size' => filesize($realFilePath)
      ]);

      // Set headers for file download
      header('Content-Type: ' . $mimeType);
      header('Content-Length: ' . filesize($realFilePath));
      header('Content-Disposition: attachment; filename="' . addslashes($safeFilename) . '"');
      header('Cache-Control: no-cache, must-revalidate');
      header('Pragma: no-cache');
      header('Expires: 0');

      readfile($realFilePath);
      exit;

    } catch (Exception $e) {
      $logger->logError('FILE_DOWNLOAD_EXCEPTION', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? 'unknown',
        'filename' => $filename ?? 'unknown'
      ]);
      $this->sendOutput(
        array('error' => "An unexpected error occurred."),
        array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
      );
    }
  }

  /**
   * Generate a secure filename to prevent path traversal and collisions
   *
   * @param string $originalName Original filename
   * @param string $extension File extension
   * @return string Secure filename
   */
  private function generateSecureFileName(string $originalName, string $extension): string
  {
    // Remove any path components and dangerous characters
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));

    // Limit filename length
    $safeName = substr($safeName, 0, 100);

    // Add timestamp and random component to prevent overwrites
    $timestamp = time();
    $random = bin2hex(random_bytes(8));

    return $safeName . '_' . $timestamp . '_' . $random . '.' . $extension;
  }

  /**
   * Get human-readable upload error message
   *
   * @param int $errorCode PHP upload error code
   * @return string Error message
   */
  private function getUploadErrorMessage(int $errorCode): string
  {
    switch ($errorCode) {
      case UPLOAD_ERR_INI_SIZE:
        return "File exceeds server upload limit.";
      case UPLOAD_ERR_FORM_SIZE:
        return "File exceeds form upload limit.";
      case UPLOAD_ERR_PARTIAL:
        return "File was only partially uploaded.";
      case UPLOAD_ERR_NO_FILE:
        return "No file was uploaded.";
      case UPLOAD_ERR_NO_TMP_DIR:
        return "Missing temporary upload directory.";
      case UPLOAD_ERR_CANT_WRITE:
        return "Failed to write file to disk.";
      case UPLOAD_ERR_EXTENSION:
        return "Upload blocked by PHP extension.";
      default:
        return "Unknown upload error.";
    }
  }
}