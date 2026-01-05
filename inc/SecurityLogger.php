<?php
/**
 * SecurityLogger - Centralized security event logging
 *
 * Logs security-related events including:
 * - Authentication attempts (success/failure)
 * - Authorization violations
 * - Suspicious activities
 * - Token validation failures
 * - Data access events
 */
class SecurityLogger
{
    private const LOG_FILE = __DIR__ . '/../logs/security.log';
    private const MAX_LOG_SIZE = 10485760; // 10MB
    private const BACKUP_COUNT = 5;

    private static $instance = null;

    private function __construct()
    {
        $this->ensureLogDirectory();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): SecurityLogger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory(): void
    {
        $logDir = dirname(self::LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0750, true);
        }
    }

    /**
     * Rotate log file if it exceeds maximum size
     */
    private function rotateLogIfNeeded(): void
    {
        if (!file_exists(self::LOG_FILE)) {
            return;
        }

        if (filesize(self::LOG_FILE) > self::MAX_LOG_SIZE) {
            for ($i = self::BACKUP_COUNT - 1; $i > 0; $i--) {
                $oldFile = self::LOG_FILE . '.' . $i;
                $newFile = self::LOG_FILE . '.' . ($i + 1);
                if (file_exists($oldFile)) {
                    rename($oldFile, $newFile);
                }
            }
            rename(self::LOG_FILE, self::LOG_FILE . '.1');
        }
    }

    /**
     * Get client IP address (considers proxies)
     */
    private function getClientIp(): string
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($ipHeaders as $header) {
            if (isset($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Get request details for logging
     */
    private function getRequestContext(): array
    {
        return [
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Write log entry
     */
    private function writeLog(string $level, string $event, array $data = []): void
    {
        $this->rotateLogIfNeeded();

        $context = $this->getRequestContext();
        $logEntry = array_merge(['level' => $level, 'event' => $event], $context, $data);

        $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES) . PHP_EOL;

        file_put_contents(self::LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log successful login
     */
    public function logLoginSuccess(string $username, int $userId): void
    {
        $this->writeLog('INFO', 'LOGIN_SUCCESS', [
            'username' => $username,
            'user_id' => $userId
        ]);
    }

    /**
     * Log failed login attempt
     */
    public function logLoginFailure(string $username, string $reason = 'Invalid credentials'): void
    {
        $this->writeLog('WARNING', 'LOGIN_FAILURE', [
            'username' => $username,
            'reason' => $reason
        ]);
    }

    /**
     * Log JWT token validation failure
     */
    public function logTokenValidationFailure(string $reason, ?string $token = null): void
    {
        $data = ['reason' => $reason];

        // Only log partial token for debugging (first 20 chars)
        if ($token) {
            $data['token_prefix'] = substr($token, 0, 20) . '...';
        }

        $this->writeLog('WARNING', 'TOKEN_VALIDATION_FAILURE', $data);
    }

    /**
     * Log authorization failure (insufficient permissions)
     */
    public function logAuthorizationFailure(string $resource, string $action, ?int $userId = null, ?string $role = null): void
    {
        $this->writeLog('WARNING', 'AUTHORIZATION_FAILURE', [
            'resource' => $resource,
            'action' => $action,
            'user_id' => $userId,
            'role' => $role
        ]);
    }

    /**
     * Log suspicious activity
     */
    public function logSuspiciousActivity(string $description, array $additionalData = []): void
    {
        $this->writeLog('CRITICAL', 'SUSPICIOUS_ACTIVITY', array_merge([
            'description' => $description
        ], $additionalData));
    }

    /**
     * Log password change
     */
    public function logPasswordChange(int $userId, string $username, bool $isReset = false): void
    {
        $event = $isReset ? 'PASSWORD_RESET' : 'PASSWORD_CHANGE';
        $this->writeLog('INFO', $event, [
            'user_id' => $userId,
            'username' => $username
        ]);
    }

    /**
     * Log user creation
     */
    public function logUserCreation(int $userId, string $username, ?int $createdBy = null): void
    {
        $this->writeLog('INFO', 'USER_CREATED', [
            'user_id' => $userId,
            'username' => $username,
            'created_by' => $createdBy
        ]);
    }

    /**
     * Log user deletion
     */
    public function logUserDeletion(int $userId, string $username, int $deletedBy): void
    {
        $this->writeLog('WARNING', 'USER_DELETED', [
            'user_id' => $userId,
            'username' => $username,
            'deleted_by' => $deletedBy
        ]);
    }

    /**
     * Log role/permission change
     */
    public function logRoleChange(int $userId, string $username, string $oldRole, string $newRole, int $changedBy): void
    {
        $this->writeLog('WARNING', 'ROLE_CHANGED', [
            'user_id' => $userId,
            'username' => $username,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'changed_by' => $changedBy
        ]);
    }

    /**
     * Log sensitive data access
     */
    public function logDataAccess(string $resource, int $resourceId, int $userId, string $action = 'VIEW'): void
    {
        $this->writeLog('INFO', 'DATA_ACCESS', [
            'resource' => $resource,
            'resource_id' => $resourceId,
            'user_id' => $userId,
            'action' => $action
        ]);
    }

    /**
     * Log logout
     */
    public function logLogout(int $userId, string $username): void
    {
        $this->writeLog('INFO', 'LOGOUT', [
            'user_id' => $userId,
            'username' => $username
        ]);
    }

    /**
     * Log token refresh
     */
    public function logTokenRefresh(int $userId, string $username): void
    {
        $this->writeLog('INFO', 'TOKEN_REFRESH', [
            'user_id' => $userId,
            'username' => $username
        ]);
    }

    /**
     * Log multiple failed attempts from same IP (potential brute force)
     */
    public function logBruteForceAttempt(string $username, int $attemptCount): void
    {
        $this->writeLog('CRITICAL', 'BRUTE_FORCE_DETECTED', [
            'username' => $username,
            'attempt_count' => $attemptCount
        ]);
    }

    /**
     * Log rate limiting trigger
     */
    public function logRateLimitExceeded(string $endpoint, ?int $userId = null): void
    {
        $this->writeLog('WARNING', 'RATE_LIMIT_EXCEEDED', [
            'endpoint' => $endpoint,
            'user_id' => $userId
        ]);
    }
}
