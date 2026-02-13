<?php
/**
 * Database Restore Script
 *
 * Restores a MySQL database from a compressed backup file
 *
 * Usage: php restore-database.php <backup-file>
 * Example: php restore-database.php backups/memory_backup_2026-01-18_14-30-00.sql.gz
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Check arguments
if ($argc < 2) {
    echo "Usage: php restore-database.php <backup-file>\n";
    echo "Example: php restore-database.php backups/memory_backup_2026-01-18_14-30-00.sql.gz\n";
    exit(1);
}

$backupFile = $argv[1];

// Validate backup file
if (!file_exists($backupFile)) {
    echo "Error: Backup file not found: $backupFile\n";
    exit(1);
}

// Validate environment variables
$requiredEnvVars = ['DB_HOST', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE'];
foreach ($requiredEnvVars as $var) {
    if (empty($_ENV[$var])) {
        die("Error: Environment variable $var is not set\n");
    }
}

$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$database = $_ENV['DB_DATABASE'];

echo "=== Database Restore ===\n";
echo "Backup file: $backupFile\n";
echo "Database: $database\n";
echo "\nWARNING: This will replace ALL data in the database!\n";
echo "Are you sure you want to continue? (type 'yes' to confirm): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "Restore cancelled.\n";
    exit(0);
}

echo "\nStarting restore...\n";

// Decompress if needed
$isCompressed = (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz');
$tempFile = null;

if ($isCompressed) {
    echo "Decompressing backup file...\n";
    $tempFile = sys_get_temp_dir() . '/restore_' . time() . '.sql';

    $gzFile = gzopen($backupFile, 'rb');
    $sqlFile = fopen($tempFile, 'wb');

    if ($gzFile && $sqlFile) {
        while (!gzeof($gzFile)) {
            fwrite($sqlFile, gzread($gzFile, 4096));
        }
        gzclose($gzFile);
        fclose($sqlFile);
        echo "Decompressed to temporary file\n";
    } else {
        echo "Error: Could not decompress backup file\n";
        exit(1);
    }

    $sqlFile = $tempFile;
} else {
    $sqlFile = $backupFile;
}

// Build mysql command
$mysqlPath = 'mysql'; // Adjust if needed
$command = sprintf(
    '%s --host=%s --user=%s --password=%s %s < %s 2>&1',
    escapeshellarg($mysqlPath),
    escapeshellarg($host),
    escapeshellarg($user),
    escapeshellarg($password),
    escapeshellarg($database),
    escapeshellarg($sqlFile)
);

// Execute restore
exec($command, $output, $returnCode);

// Cleanup temporary file
if ($tempFile && file_exists($tempFile)) {
    unlink($tempFile);
}

if ($returnCode !== 0) {
    echo "Error during restore:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}

echo "\n=== Restore Completed Successfully ===\n";
echo "Database '$database' has been restored from: $backupFile\n";

exit(0);
