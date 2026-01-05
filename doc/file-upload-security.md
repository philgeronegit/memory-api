# File Upload Security

## Overview

The file upload system has been hardened with comprehensive security controls to prevent common vulnerabilities identified in OWASP Top 10.

## Security Features Implemented

### 1. Access Control (A01: Broken Access Control)

**Authorization Checks:**
- Users can only upload files to their own directory
- Admin and Project Manager roles can upload for any user
- All access attempts are logged for audit trails

**Implementation:**
```php
// Verify user is uploading to their own directory or has admin permissions
if ($currentUser->id_user != $userId && !in_array($currentUser->role, ['admin', 'projectManager'])) {
    $logger->logAuthorizationFailure(...);
    // Return 403 Forbidden
}
```

### 2. File Validation (A03: Injection)

**Multi-Layer File Type Validation:**

1. **MIME Type Validation** - Uses `finfo_file()` to check actual file content
2. **Extension Validation** - Validates file extension against whitelist
3. **Both checks must pass** - Prevents MIME type spoofing attacks

**Allowed File Types:**
- Images: JPG, JPEG, PNG, GIF
- Documents: PDF

```php
private const ALLOWED_MIME_TYPES = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf'
];

private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
```

### 3. File Size Limits (A05: Security Misconfiguration)

**Maximum File Size:** 5MB (5,242,880 bytes)

This prevents:
- Denial of Service (DoS) attacks via large file uploads
- Disk space exhaustion
- Memory exhaustion

```php
if ($file['size'] > self::MAX_FILE_SIZE) {
    // Log and reject with 413 Payload Too Large
}
```

### 4. Path Traversal Prevention (A01: Broken Access Control)

**Secure Filename Generation:**
- Removes all path components from uploaded filename
- Strips dangerous characters
- Adds timestamp and random component to prevent overwrites
- Prevents attacks like `../../etc/passwd`

```php
private function generateSecureFileName(string $originalName, string $extension): string
{
    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $safeName = substr($safeName, 0, 100);
    $timestamp = time();
    $random = bin2hex(random_bytes(8));

    return $safeName . '_' . $timestamp . '_' . $random . '.' . $extension;
}
```

**Directory Traversal Protection in Listing:**
- Uses `realpath()` to resolve symbolic links
- Verifies path stays within allowed upload directory
- Rejects any attempt to access parent directories

### 5. Secure File Permissions (A05: Security Misconfiguration)

**Directory Permissions:** `0750` (rwxr-x---)
- Owner (web server): Full access
- Group: Read and execute
- Others: No access

**File Permissions:** `0640` (rw-r-----)
- Owner (web server): Read and write
- Group: Read only
- Others: No access

This prevents:
- Execution of uploaded files as scripts
- Unauthorized access by other users
- Directory listing by unauthorized parties

### 6. Upload Validation (A08: Software and Data Integrity Failures)

**PHP's Built-in Validation:**
```php
if (!is_uploaded_file($file['tmp_name'])) {
    // Reject - file was not uploaded via HTTP POST
}
```

This prevents:
- Local file inclusion attacks
- Arbitrary file access
- Tampering with upload process

### 7. Comprehensive Error Handling

**Upload Error Detection:**
- Checks `$_FILES['file']['error']` for PHP upload errors
- Provides meaningful error messages to users
- Logs all errors for security monitoring

**Error Codes Handled:**
- `UPLOAD_ERR_INI_SIZE` - File exceeds server limit
- `UPLOAD_ERR_FORM_SIZE` - File exceeds form limit
- `UPLOAD_ERR_PARTIAL` - Partial upload
- `UPLOAD_ERR_NO_FILE` - No file uploaded
- `UPLOAD_ERR_NO_TMP_DIR` - Missing temp directory
- `UPLOAD_ERR_CANT_WRITE` - Disk write failure
- `UPLOAD_ERR_EXTENSION` - PHP extension blocked upload

### 8. Security Logging

**All Security Events Logged:**

| Event | Log Level | Trigger |
|-------|-----------|---------|
| `FILE_UPLOADED` | INFO | Successful upload |
| `INVALID_USER_ID` | SUSPICIOUS | Invalid or non-numeric user ID |
| `FILE_SIZE_EXCEEDED` | SUSPICIOUS | File exceeds 5MB limit |
| `INVALID_FILE_TYPE` | SUSPICIOUS | MIME type not in whitelist |
| `INVALID_FILE_EXTENSION` | SUSPICIOUS | Extension not in whitelist |
| `FILE_UPLOAD_TAMPERING` | SUSPICIOUS | Failed `is_uploaded_file()` check |
| `DIRECTORY_TRAVERSAL_ATTEMPT` | SUSPICIOUS | Path validation failure in listing |
| `AUTHORIZATION_FAILURE` | WARNING | User lacks permission |
| `FILE_UPLOAD_ERROR` | SUSPICIOUS | PHP upload error occurred |
| `FILE_UPLOAD_EXCEPTION` | ERROR | Unexpected exception |

**Log Example:**
```json
{
  "level": "INFO",
  "event": "FILE_UPLOADED",
  "user_id": 42,
  "filename": "document_1736115000_a1b2c3d4e5f6g7h8.pdf",
  "original_filename": "my-document.pdf",
  "file_size": 245678,
  "mime_type": "application/pdf",
  "uploaded_by": 42,
  "timestamp": "2026-01-05 14:30:00"
}
```

## API Endpoints

### Upload File

**Endpoint:** `POST /memory/upload/{userId}`

**Authentication:** Required (JWT token)

**Authorization:**
- Users can upload to their own directory (`userId` matches token)
- Admin/Project Manager can upload for any user

**Request:**
- Content-Type: `multipart/form-data`
- Form field: `file`

**Response Success (200 OK):**
```json
{
  "status": "success",
  "message": "File uploaded successfully.",
  "filename": "vacation_photo_1736115000_a1b2c3d4e5f6g7h8.jpg",
  "size": 1048576
}
```

**Response Errors:**
- `400 Bad Request` - Invalid input, file type, or size
- `403 Forbidden` - Unauthorized access
- `413 Payload Too Large` - File exceeds 5MB
- `500 Internal Server Error` - Server-side failure

### List Files

**Endpoint:** `GET /memory/upload/{userId}`

**Authentication:** Required (JWT token)

**Authorization:**
- Users can list their own files
- Admin/Project Manager can list any user's files

**Response Success (200 OK):**
```json
[
  {
    "name": "document_1736115000_a1b2c3d4e5f6g7h8.pdf",
    "size": 245678,
    "modified": "2026-01-05 14:30:00"
  },
  {
    "name": "photo_1736114000_b2c3d4e5f6g7h8i9.jpg",
    "size": 1048576,
    "modified": "2026-01-05 14:15:00"
  }
]
```

**Response Errors:**
- `400 Bad Request` - Invalid user ID
- `403 Forbidden` - Unauthorized access
- `404 Not Found` - No files found for user

## Configuration

### Constants

Located in [UploadController.php](../Controllers/Api/UploadController.php):

```php
private const MAX_FILE_SIZE = 5242880; // 5MB in bytes
private const UPLOAD_DIR = __DIR__ . '/../../uploads/';
private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
```

### Directory Structure

```
uploads/
├── 1/               # User ID 1's files
│   ├── file1.jpg
│   └── file2.pdf
├── 2/               # User ID 2's files
│   └── file3.png
└── ...
```

## Security Best Practices

### For Developers

1. **Never trust user input** - All filenames and paths are sanitized
2. **Validate on multiple levels** - MIME type + extension + size
3. **Use absolute paths** - Prevents path manipulation
4. **Log security events** - Enables threat detection and forensics
5. **Fail securely** - Default deny, explicit allow
6. **Principle of least privilege** - Minimal file/directory permissions

### For System Administrators

1. **Monitor logs regularly** - Check `logs/security.log` for suspicious activity
2. **Set up log rotation** - Automatic rotation at 10MB (5 backups)
3. **Configure disk quotas** - Prevent disk exhaustion
4. **Regular security audits** - Review uploaded files periodically
5. **Antivirus scanning** - Consider integrating ClamAV or similar
6. **Backup strategy** - Regular backups of uploads directory

## Known Limitations

1. **No virus scanning** - Files are validated but not scanned for malware
2. **No content-based validation** - PDF/image structure is not verified
3. **No deduplication** - Same file can be uploaded multiple times
4. **No compression** - Large images are not automatically compressed

## Future Enhancements

1. **Virus scanning integration** (ClamAV)
2. **Image manipulation** (thumbnail generation, resizing)
3. **Content Security Policy** for served files
4. **File encryption at rest**
5. **Digital signature verification** for PDFs
6. **Rate limiting** to prevent upload flooding
7. **File expiration** and automatic cleanup
8. **S3/cloud storage** integration for scalability

## OWASP Top 10 Compliance

| OWASP Risk | Mitigation |
|------------|-----------|
| **A01: Broken Access Control** | User ID validation, role-based authorization, path traversal prevention |
| **A03: Injection** | Input sanitization, MIME type validation, secure filename generation |
| **A04: Insecure Design** | Defense in depth, fail-secure defaults, separation of concerns |
| **A05: Security Misconfiguration** | Secure file permissions (0640), directory permissions (0750), size limits |
| **A08: Software and Data Integrity** | `is_uploaded_file()` validation, error checking |
| **A09: Security Logging Failures** | Comprehensive logging of all security events |
| **A10: Server-Side Request Forgery** | N/A - No remote file fetching |

## Testing

### Manual Testing with curl

**Upload file:**
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "file=@/path/to/file.jpg" \
  http://localhost:8000/memory/upload/42
```

**List files:**
```bash
curl -X GET \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://localhost:8000/memory/upload/42
```

### Security Testing Scenarios

1. **Path Traversal Test:**
   - Upload file with name: `../../etc/passwd.jpg`
   - Expected: Filename sanitized, no path traversal

2. **MIME Type Spoofing:**
   - Rename `malicious.php` to `malicious.jpg`
   - Expected: Rejected due to MIME type mismatch

3. **Size Limit Test:**
   - Upload file larger than 5MB
   - Expected: 413 Payload Too Large response

4. **Authorization Test:**
   - User A tries to upload to User B's directory
   - Expected: 403 Forbidden response

5. **Extension Validation:**
   - Upload file with `.exe` or `.php` extension
   - Expected: 400 Bad Request response

## References

- [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)
- [OWASP Top 10 2021](https://owasp.org/Top10/)
- [PHP File Upload Best Practices](https://www.php.net/manual/en/features.file-upload.php)
- [CWE-434: Unrestricted Upload of File with Dangerous Type](https://cwe.mitre.org/data/definitions/434.html)
- [CWE-22: Path Traversal](https://cwe.mitre.org/data/definitions/22.html)
