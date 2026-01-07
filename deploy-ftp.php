#!/usr/bin/env php
<?php
/**
 * FTP Deployment Script for Memory API
 *
 * This script automates the deployment of the Memory API to a remote server via FTP.
 * It creates timestamped backups before deployment and uploads all necessary files.
 *
 * Requirements:
 * - PHP FTP extension (usually enabled by default)
 * - .env.deploy file with FTP credentials
 *
 * Usage:
 *   php deploy-ftp.php              # Deploy to production
 *   php deploy-ftp.php --dry-run    # Preview what would be deployed
 *   php deploy-ftp.php --help       # Show help message
 */

class FTPDeployer
{
    private $config;
    private $deployConfig;
    private $connection;
    private $isDryRun = false;
    private $stats = [
        'files_uploaded' => 0,
        'files_skipped' => 0,
        'total_size' => 0,
        'errors' => []
    ];

    public function __construct($isDryRun = false)
    {
        $this->isDryRun = $isDryRun;
        $this->loadConfiguration();
        $this->loadDeployConfig();
    }

    private function loadConfiguration()
    {
        $envFile = __DIR__ . '/.env.deploy';
        if (!file_exists($envFile)) {
            $this->error("Deployment configuration file not found: .env.deploy");
            $this->info("Please copy .env.deploy.example to .env.deploy and configure it.");
            exit(1);
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $config[$key] = $value;
        }

        $required = ['FTP_HOST', 'FTP_USER', 'FTP_PASSWORD', 'FTP_REMOTE_PATH'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                $this->error("Missing required configuration: $key");
                exit(1);
            }
        }

        $this->config = [
            'host' => $config['FTP_HOST'],
            'port' => $config['FTP_PORT'] ?? 21,
            'username' => $config['FTP_USER'],
            'password' => $config['FTP_PASSWORD'],
            'remote_path' => rtrim($config['FTP_REMOTE_PATH'], '/'),
            'use_passive' => ($config['FTP_PASSIVE'] ?? 'true') === 'true',
            'backup_location' => $config['BACKUP_LOCATION'] ?? 'sibling',
            'backup_keep_count' => (int)($config['BACKUP_KEEP_COUNT'] ?? 5)
        ];
    }

    private function loadDeployConfig()
    {
        $configFile = __DIR__ . '/.deploy-config.json';
        if (!file_exists($configFile)) {
            $this->error("Deploy configuration file not found: .deploy-config.json");
            exit(1);
        }

        $json = file_get_contents($configFile);
        $this->deployConfig = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON in .deploy-config.json: " . json_last_error_msg());
            exit(1);
        }
    }

    public function deploy()
    {
        $this->info("=== Memory API FTP Deployment ===\n");

        if ($this->isDryRun) {
            $this->warning("DRY RUN MODE - No changes will be made\n");
        }

        if (!extension_loaded('ftp')) {
            $this->error("PHP FTP extension is not installed.");
            $this->info("Enable it in your php.ini file.");
            exit(1);
        }

        $this->buildVendor();
        $this->connect();
        $this->createBackup();
        $this->uploadFiles();
        $this->cleanupOldBackups();
        $this->displaySummary();
        $this->disconnect();
    }

    private function buildVendor()
    {
        $this->section("Building Vendor Directory");

        if ($this->isDryRun) {
            $this->info("Would run: composer install --no-dev --optimize-autoloader --no-interaction");
            return;
        }

        $this->info("Running: composer install --no-dev --optimize-autoloader --no-interaction");

        $output = [];
        $returnCode = 0;
        exec('composer install --no-dev --optimize-autoloader --no-interaction 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Composer install failed:");
            foreach ($output as $line) {
                echo "  $line\n";
            }
            exit(1);
        }

        $this->success("Vendor directory built successfully\n");
    }

    private function connect()
    {
        $this->section("Connecting to FTP Server");

        if ($this->isDryRun) {
            $this->info("Would connect to: {$this->config['username']}@{$this->config['host']}:{$this->config['port']}");
            return;
        }

        $this->info("Connecting to {$this->config['host']}:{$this->config['port']}...");

        $this->connection = ftp_connect($this->config['host'], $this->config['port'], 30);

        if (!$this->connection) {
            $this->error("Failed to connect to FTP server");
            exit(1);
        }

        if (!ftp_login($this->connection, $this->config['username'], $this->config['password'])) {
            $this->error("FTP authentication failed");
            ftp_close($this->connection);
            exit(1);
        }

        if ($this->config['use_passive']) {
            ftp_pasv($this->connection, true);
        }

        $this->success("Connected successfully\n");
    }

    private function createBackup()
    {
        $this->section("Creating Backup");

        $timestamp = date('Ymd_His');
        $remotePath = $this->config['remote_path'];
        $remoteDir = dirname($remotePath);
        $appName = basename($remotePath);

        if ($this->config['backup_location'] === 'backups') {
            $backupPath = $remoteDir . '/backups/' . $appName . '_backup_' . $timestamp;
        } else {
            $backupPath = $remoteDir . '/' . $appName . '_backup_' . $timestamp;
        }

        $this->info("Backup location: $backupPath");

        if ($this->isDryRun) {
            $this->info("Would create backup at: $backupPath");
            return;
        }

        if (!$this->remoteDirectoryExists($remotePath)) {
            $this->warning("Remote directory does not exist yet, skipping backup\n");
            return;
        }

        $currentDir = ftp_pwd($this->connection);

        if ($this->config['backup_location'] === 'backups') {
            $backupsDir = $remoteDir . '/backups';
            $this->createRemoteDirectory($backupsDir);
        }

        $this->info("Creating backup (this may take a moment)...");
        if ($this->copyRemoteDirectory($remotePath, $backupPath)) {
            $this->success("Backup created successfully\n");
        } else {
            $this->warning("Failed to create complete backup (continuing anyway)\n");
        }

        ftp_chdir($this->connection, $currentDir);
    }

    private function copyRemoteDirectory($source, $destination)
    {
        if ($this->isDryRun) {
            return true;
        }

        $parent = dirname($destination);
        $this->createRemoteDirectory($parent);

        if (!@ftp_mkdir($this->connection, $destination)) {
            return false;
        }

        $contents = @ftp_nlist($this->connection, $source);
        if ($contents === false) {
            return false;
        }

        foreach ($contents as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemName = basename($item);
            if ($itemName === '.' || $itemName === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $itemName;
            $destPath = $destination . '/' . $itemName;

            $size = @ftp_size($this->connection, $sourcePath);

            if ($size === -1) {
                $this->copyRemoteDirectory($sourcePath, $destPath);
            } else {
                $tempFile = tempnam(sys_get_temp_dir(), 'ftp_backup_');
                if (@ftp_get($this->connection, $tempFile, $sourcePath, FTP_BINARY)) {
                    @ftp_put($this->connection, $destPath, $tempFile, FTP_BINARY);
                }
                @unlink($tempFile);
            }
        }

        return true;
    }

    private function uploadFiles()
    {
        $this->section("Uploading Files");

        $localBase = __DIR__;
        $remoteBase = $this->config['remote_path'];

        $this->createRemoteDirectory($remoteBase);

        $filesToUpload = $this->getFilesToUpload();

        $this->info("Found " . count($filesToUpload) . " files to upload\n");

        foreach ($filesToUpload as $relativePath) {
            $relativePath = str_replace('\\', '/', $relativePath);
            $relativePath = preg_replace('#/+#', '/', $relativePath);

            $localPath = $localBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $remotePath = $remoteBase . '/' . $relativePath;

            $this->uploadFile($localPath, $remotePath, $relativePath);
        }

        $this->createRequiredDirectories($remoteBase);
    }

    private function getFilesToUpload()
    {
        $files = [];
        $localBase = __DIR__;

        foreach ($this->deployConfig['include'] as $path) {
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
            $fullPath = $localBase . DIRECTORY_SEPARATOR . $path;

            if (is_file($fullPath)) {
                $files[] = str_replace('\\', '/', $path);
            } elseif (is_dir($fullPath)) {
                $files = array_merge($files, $this->scanDirectory($path));
            }
        }

        return array_filter($files, function($file) {
            return !$this->shouldExclude($file);
        });
    }

    private function scanDirectory($relativePath)
    {
        $files = [];
        $localBase = __DIR__;
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $fullPath = $localBase . DIRECTORY_SEPARATOR . $relativePath;

        if (!is_dir($fullPath)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $absolutePath = $file->getPathname();

                $relativeToBase = substr($absolutePath, strlen($localBase) + 1);
                $relativeToBase = str_replace('\\', '/', $relativeToBase);

                $files[] = $relativeToBase;
            }
        }

        return $files;
    }

    private function shouldExclude($path)
    {
        foreach ($this->deployConfig['exclude'] as $pattern) {
            $pattern = str_replace('/', '\/', $pattern);
            $pattern = str_replace('*', '.*', $pattern);

            if (preg_match('/^' . $pattern . '/', $path)) {
                return true;
            }
        }

        return false;
    }

    private function uploadFile($localPath, $remotePath, $displayPath)
    {
        $localPath = $this->normalizePath($localPath);
        $remoteDir = dirname($remotePath);
        $this->createRemoteDirectory($remoteDir);

        if ($this->isDryRun) {
            $size = filesize($localPath);
            $this->stats['files_uploaded']++;
            $this->stats['total_size'] += $size;
            echo "  [DRY RUN] $displayPath (" . $this->formatBytes($size) . ")\n";
            return;
        }

        if (@ftp_put($this->connection, $remotePath, $localPath, FTP_BINARY)) {
            $size = filesize($localPath);
            $this->stats['files_uploaded']++;
            $this->stats['total_size'] += $size;
            echo "  ✓ $displayPath (" . $this->formatBytes($size) . ")\n";
        } else {
            $this->stats['errors'][] = $displayPath;
            echo "  ✗ $displayPath [FAILED]\n";
        }
    }

    private function createRemoteDirectory($path)
    {
        if ($this->isDryRun) {
            return true;
        }

        if ($this->remoteDirectoryExists($path)) {
            return true;
        }

        $parts = explode('/', trim($path, '/'));
        $currentPath = '';

        foreach ($parts as $part) {
            $currentPath .= '/' . $part;

            if (!$this->remoteDirectoryExists($currentPath)) {
                @ftp_mkdir($this->connection, $currentPath);
            }
        }

        return true;
    }

    private function remoteDirectoryExists($path)
    {
        if ($this->isDryRun) {
            return false;
        }

        $currentDir = @ftp_pwd($this->connection);
        if (@ftp_chdir($this->connection, $path)) {
            ftp_chdir($this->connection, $currentDir);
            return true;
        }
        return false;
    }

    private function createRequiredDirectories($remoteBase)
    {
        if (!isset($this->deployConfig['required_directories'])) {
            return;
        }

        $this->info("\nCreating required directories:");

        foreach ($this->deployConfig['required_directories'] as $dir) {
            $remotePath = $remoteBase . '/' . $dir;

            if ($this->isDryRun) {
                echo "  [DRY RUN] Would create: $dir\n";
            } else {
                $this->createRemoteDirectory($remotePath);
                echo "  ✓ $dir\n";
            }
        }
    }

    private function cleanupOldBackups()
    {
        if ($this->isDryRun || $this->config['backup_keep_count'] <= 0) {
            return;
        }

        $this->section("Cleaning Up Old Backups");

        $remotePath = $this->config['remote_path'];
        $remoteDir = dirname($remotePath);
        $appName = basename($remotePath);

        $backupsDir = $this->config['backup_location'] === 'backups'
            ? $remoteDir . '/backups'
            : $remoteDir;

        if (!$this->remoteDirectoryExists($backupsDir)) {
            return;
        }

        $backups = @ftp_nlist($this->connection, $backupsDir);
        if ($backups === false) {
            return;
        }

        $backupDirs = [];
        foreach ($backups as $item) {
            $basename = basename($item);
            if (strpos($basename, $appName . '_backup_') === 0) {
                $backupDirs[] = $item;
            }
        }

        rsort($backupDirs);

        if (count($backupDirs) <= $this->config['backup_keep_count']) {
            $this->info("No old backups to clean up\n");
            return;
        }

        $toDelete = array_slice($backupDirs, $this->config['backup_keep_count']);

        foreach ($toDelete as $backup) {
            $this->info("Removing old backup: " . basename($backup));
            $this->deleteRemoteDirectory($backup);
        }

        $this->success("Cleaned up " . count($toDelete) . " old backup(s)\n");
    }

    private function deleteRemoteDirectory($path)
    {
        if ($this->isDryRun) {
            return true;
        }

        $contents = @ftp_nlist($this->connection, $path);
        if ($contents === false) {
            @ftp_rmdir($this->connection, $path);
            return true;
        }

        foreach ($contents as $item) {
            $itemName = basename($item);
            if ($itemName === '.' || $itemName === '..') {
                continue;
            }

            $itemPath = $path . '/' . $itemName;
            $size = @ftp_size($this->connection, $itemPath);

            if ($size === -1) {
                $this->deleteRemoteDirectory($itemPath);
            } else {
                @ftp_delete($this->connection, $itemPath);
            }
        }

        @ftp_rmdir($this->connection, $path);
        return true;
    }

    private function displaySummary()
    {
        $this->section("Deployment Summary");

        echo "Files uploaded: {$this->stats['files_uploaded']}\n";
        echo "Total size: " . $this->formatBytes($this->stats['total_size']) . "\n";

        if (!empty($this->stats['errors'])) {
            echo "Errors: " . count($this->stats['errors']) . "\n";
            foreach ($this->stats['errors'] as $error) {
                echo "  - $error\n";
            }
        }

        if ($this->isDryRun) {
            $this->warning("\nDRY RUN COMPLETE - No changes were made");
        } else {
            $this->success("\n✓ Deployment completed successfully!");
            $this->info("\nNext steps:");
            echo "  1. Set proper file permissions on the server\n";
            echo "  2. Create/update .env file with production credentials\n";
            echo "  3. Update CORS settings in index.php\n";
            echo "  4. Import SQL files to database\n";
            echo "\nSee DEPLOYMENT.md for detailed instructions.\n";
        }
    }

    private function disconnect()
    {
        if ($this->connection && !$this->isDryRun) {
            ftp_close($this->connection);
        }
    }

    private function normalizePath($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        return $path;
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function section($title)
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo $title . "\n";
        echo str_repeat('=', 60) . "\n";
    }

    private function info($message)
    {
        echo "\033[0;36m$message\033[0m\n";
    }

    private function success($message)
    {
        echo "\033[0;32m$message\033[0m\n";
    }

    private function warning($message)
    {
        echo "\033[0;33m$message\033[0m\n";
    }

    private function error($message)
    {
        echo "\033[0;31m$message\033[0m\n";
    }
}

function showHelp()
{
    echo <<<HELP
Memory API FTP Deployment Script

Usage:
  php deploy-ftp.php [options]

Options:
  --dry-run    Preview deployment without making changes
  --help       Show this help message

Description:
  This script deploys the Memory API to a remote server via FTP.
  It performs the following actions:

  1. Builds vendor/ directory with production dependencies
  2. Creates a timestamped backup of the remote application
  3. Uploads all application files to the remote server
  4. Creates required directories (uploads/, logs/)
  5. Cleans up old backups based on retention policy

Configuration:
  Create .env.deploy file with your FTP credentials.
  See .env.deploy.example for required variables.

HELP;
}

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$options = getopt('', ['dry-run', 'help']);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

$isDryRun = isset($options['dry-run']);

try {
    $deployer = new FTPDeployer($isDryRun);
    $deployer->deploy();
} catch (Exception $e) {
    echo "\033[0;31mDeployment failed: {$e->getMessage()}\033[0m\n";
    exit(1);
}
