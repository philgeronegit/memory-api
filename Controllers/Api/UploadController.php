
<?php
class UploadController extends BaseController
{
  public function __construct()
  {
    parent::__construct(new NoteModel());
  }

  public function addAction(): void
  {
    $targetDir = "uploads/";
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode('/', $uri);
    $userId = $uri[3];
    if (empty($userId)) {
      $this->sendOutput(
        json_encode(array("status" => "error", "message" => "User id is required.")),
        array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
      );
      return;
    }
    $targetFilePath = $targetDir . $userId . '/';
    if (!file_exists($targetFilePath)) {
      mkdir($targetFilePath, 0777, true);
    }

    if (isset($_FILES['file'])) {
      $file = $_FILES['file'];
      $fileName = basename($file['name']);
      $targetFilePath = $targetDir . $userId . '/' . $fileName;
      $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

      // Allow certain file formats
      $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
      if (in_array($fileType, $allowTypes)) {
          if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            $this->sendOutput(
              json_encode(array("status" => "success", "message" => "File uploaded successfully.")),
              array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
          } else {
              $this->sendOutput(
                json_encode(array("status" => "error", "message" => "File upload failed.")),
                array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
              );
          }
      } else {
          $this->sendOutput(
            json_encode(array("status" => "error", "message" => "Sorry, only JPG, JPEG, PNG, GIF, & PDF files are allowed.")),
            array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
          );
      }
    } else {
        $this->sendOutput(
          json_encode(array("status" => "error", "message" => "No file was uploaded.")),
          array('Content-Type: application/json', 'HTTP/1.1 400 Bad Request')
        );
    }
  }

  public function listAction($args = [])
  {
    $userId = $args['id'];
    $targetDir = "uploads/" . $userId . '/';

    if (!file_exists($targetDir)) {
      $this->sendOutput(
        json_encode(array('error' => "Directory does not exist.")),
        array('Content-Type: application/json', 'HTTP/1.1 404 Not Found')
      );
      return;
    }

    $files = array_diff(scandir($targetDir), array('.', '..'));
    $fileList = [];

    foreach ($files as $file) {
      $fileList[] = array(
        'name' => $file,
        'path' => $targetDir . $file
      );
    }

    $this->sendOutput(
      json_encode($fileList),
      array('Content-Type: application/json', 'HTTP/1.1 200 OK')
    );
  }
}