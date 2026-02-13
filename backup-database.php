<?php
/**
 * Automated MySQL Database Backup Script
 *
 * This script creates compressed backups of the Memory database
 * with automatic rotation and optional remote upload.
 *
 * Usage: php backup-database.php [--upload-ftp]
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuration
$backupDir = __DIR__ . '/backups';
$maxLocalBackups = 30; // Keep last 30 backups
$uploadToFtp = in_array('--upload-ftp', $argv ?? []);

// Ensure backup directory exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "Created backup directory: $backupDir\n";
}

// Generate filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$filename = "memory_backup_{$timestamp}.sql";
$compressedFilename = "{$filename}.gz";
$backupPath = $backupDir . '/' . $filename;
$compressedPath = $backupDir . '/' . $compressedFilename;

// Validate environment variables
$requiredEnvVars = ['DB_HOST', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE'];
foreach ($requiredEnvVars as $var) {
    if (empty($_ENV[$var])) {
        die("Error: Environment variable $var is not set\n");
    }
}

// Build mysqldump command (without password in command for security)
$mysqlDumpPath = 'mysqldump'; // Adjust if needed: 'C:\\wamp64\\bin\\mysql\\mysql8.x.x\\bin\\mysqldump.exe'
$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$database = $_ENV['DB_DATABASE'];

echo "Starting backup of database: $database\n";
echo "Timestamp: $timestamp\n";

// Use mysqldump with password from environment
$command = sprintf(
    '%s --host=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s 2>&1',
    escapeshellarg($mysqlDumpPath),
    escapeshellarg($host),
    escapeshellarg($user),
    escapeshellarg($password),
    escapeshellarg($database),
    escapeshellarg($backupPath)
);

// Execute backup
exec($command, $output, $returnCode);

if ($returnCode !== 0) {
    echo "Error during backup:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}

if (!file_exists($backupPath) || filesize($backupPath) === 0) {
    echo "Error: Backup file is empty or was not created\n";
    exit(1);
}

$backupSize = filesize($backupPath);
echo "Backup created successfully: " . round($backupSize / 1024 / 1024, 2) . " MB\n";

// Compress the backup
echo "Compressing backup...\n";
$gzFile = gzopen($compressedPath, 'w9');
$sqlFile = fopen($backupPath, 'rb');

if ($gzFile && $sqlFile) {
    while (!feof($sqlFile)) {
        gzwrite($gzFile, fread($sqlFile, 1024 * 512));
    }
    fclose($sqlFile);
    gzclose($gzFile);

    // Remove uncompressed file
    unlink($backupPath);

    $compressedSize = filesize($compressedPath);
    $compressionRatio = round((1 - $compressedSize / $backupSize) * 100, 1);
    echo "Compressed to: " . round($compressedSize / 1024 / 1024, 2) . " MB ({$compressionRatio}% reduction)\n";
} else {
    echo "Warning: Could not compress backup file\n";
}

// Cleanup old backups
echo "Cleaning up old backups (keeping last $maxLocalBackups)...\n";
$backupFiles = glob($backupDir . '/memory_backup_*.sql.gz');
if (count($backupFiles) > $maxLocalBackups) {
    // Sort by modification time
    usort($backupFiles, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });

    // Remove oldest files
    $filesToDelete = array_slice($backupFiles, 0, count($backupFiles) - $maxLocalBackups);
    foreach ($filesToDelete as $file) {
        unlink($file);
        echo "Deleted old backup: " . basename($file) . "\n";
    }
}

// Optional FTP upload
if ($uploadToFtp) {
    echo "Uploading to FTP server...\n";

    if (empty($_ENV['FTP_HOST']) || empty($_ENV['FTP_USER']) || empty($_ENV['FTP_PASSWORD'])) {
        echo "Warning: FTP credentials not configured in .env file\n";
    } else {
        $ftpConn = ftp_connect($_ENV['FTP_HOST']);
        if ($ftpConn) {
            $ftpLogin = ftp_login($ftpConn, $_ENV['FTP_USER'], $_ENV['FTP_PASSWORD']);

            if ($ftpLogin) {
                ftp_pasv($ftpConn, true);

                $remotePath = '/backups/' . $compressedFilename;
                if (ftp_put($ftpConn, $remotePath, $compressedPath, FTP_BINARY)) {
                    echo "Successfully uploaded to FTP: $remotePath\n";
                } else {
                    echo "Error uploading to FTP\n";
                }
            } else {
                echo "Error: FTP login failed\n";
            }

            ftp_close($ftpConn);
        } else {
            echo "Error: Could not connect to FTP server\n";
        }
    }
}

// Generate backup report
$report = [
    'timestamp' => $timestamp,
    'database' => $database,
    'filename' => $compressedFilename,
    'size_mb' => round(filesize($compressedPath) / 1024 / 1024, 2),
    'path' => $compressedPath,
    'uploaded_to_ftp' => $uploadToFtp
];

echo "\n=== Backup Summary ===\n";
echo json_encode($report, JSON_PRETTY_PRINT) . "\n";
echo "\nBackup completed successfully!\n";

exit(0);
