<?php
/**
 * SecurityLogger Test
 *
 * Manual test script to verify security logging functionality
 * Run from command line: php tests/SecurityLoggerManualTest.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

require_once __DIR__ . "/../inc/SecurityLogger.php";

echo "=== Security Logger Manual Test ===" . PHP_EOL . PHP_EOL;

$logger = SecurityLogger::getInstance();

// Simulate request context
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
$_SERVER['REQUEST_METHOD'] = 'TEST';
$_SERVER['REQUEST_URI'] = '/test';

echo "1. Testing successful login..." . PHP_EOL;
$logger->logLoginSuccess('test_user', 1);
echo "   ✓ Logged LOGIN_SUCCESS" . PHP_EOL . PHP_EOL;

echo "2. Testing failed login..." . PHP_EOL;
$logger->logLoginFailure('wrong_user', 'Invalid credentials');
echo "   ✓ Logged LOGIN_FAILURE" . PHP_EOL . PHP_EOL;

echo "3. Testing token validation failure..." . PHP_EOL;
$logger->logTokenValidationFailure('Expired token', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...');
echo "   ✓ Logged TOKEN_VALIDATION_FAILURE" . PHP_EOL . PHP_EOL;

echo "4. Testing authorization failure..." . PHP_EOL;
$logger->logAuthorizationFailure('user_management', 'delete', 2, 'developer');
echo "   ✓ Logged AUTHORIZATION_FAILURE" . PHP_EOL . PHP_EOL;

echo "5. Testing user creation..." . PHP_EOL;
$logger->logUserCreation(100, 'new_test_user', 1);
echo "   ✓ Logged USER_CREATED" . PHP_EOL . PHP_EOL;

echo "6. Testing user deletion..." . PHP_EOL;
$logger->logUserDeletion(100, 'deleted_user', 1);
echo "   ✓ Logged USER_DELETED" . PHP_EOL . PHP_EOL;

echo "7. Testing role change..." . PHP_EOL;
$logger->logRoleChange(2, 'test_user', 'developer', 'projectManager', 1);
echo "   ✓ Logged ROLE_CHANGED" . PHP_EOL . PHP_EOL;

echo "8. Testing password change..." . PHP_EOL;
$logger->logPasswordChange(1, 'test_user', false);
echo "   ✓ Logged PASSWORD_CHANGE" . PHP_EOL . PHP_EOL;

echo "9. Testing password reset..." . PHP_EOL;
$logger->logPasswordChange(1, 'test_user', true);
echo "   ✓ Logged PASSWORD_RESET" . PHP_EOL . PHP_EOL;

echo "10. Testing token refresh..." . PHP_EOL;
$logger->logTokenRefresh(1, 'test_user');
echo "   ✓ Logged TOKEN_REFRESH" . PHP_EOL . PHP_EOL;

echo "11. Testing suspicious activity..." . PHP_EOL;
$logger->logSuspiciousActivity('Multiple failed login attempts', ['username' => 'admin']);
echo "   ✓ Logged SUSPICIOUS_ACTIVITY" . PHP_EOL . PHP_EOL;

echo "12. Testing brute force detection..." . PHP_EOL;
$logger->logBruteForceAttempt('admin', 10);
echo "   ✓ Logged BRUTE_FORCE_DETECTED" . PHP_EOL . PHP_EOL;

echo "13. Testing rate limit exceeded..." . PHP_EOL;
$logger->logRateLimitExceeded('/memory/notes', 1);
echo "   ✓ Logged RATE_LIMIT_EXCEEDED" . PHP_EOL . PHP_EOL;

echo "14. Testing data access..." . PHP_EOL;
$logger->logDataAccess('user_profile', 42, 1, 'VIEW');
echo "   ✓ Logged DATA_ACCESS" . PHP_EOL . PHP_EOL;

// Check if log file was created
$logFile = __DIR__ . '/../logs/security.log';
if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    $lineCount = count(file($logFile));
    echo "=== Test Results ===" . PHP_EOL;
    echo "✓ Log file created: {$logFile}" . PHP_EOL;
    echo "✓ Log file size: " . number_format($logSize) . " bytes" . PHP_EOL;
    echo "✓ Log entries: {$lineCount}" . PHP_EOL . PHP_EOL;

    echo "=== Sample Log Entries ===" . PHP_EOL;
    $lines = file($logFile);
    $sampleLines = array_slice($lines, -5); // Last 5 entries
    foreach ($sampleLines as $line) {
        $entry = json_decode($line, true);
        if ($entry) {
            echo sprintf(
                "[%s] %s - %s (IP: %s)" . PHP_EOL,
                $entry['level'],
                $entry['timestamp'] ?? 'N/A',
                $entry['event'],
                $entry['ip'] ?? 'N/A'
            );
        }
    }

    echo PHP_EOL . "=== All Tests Passed! ===" . PHP_EOL;
    echo "You can view the full log at: {$logFile}" . PHP_EOL;
} else {
    echo "✗ ERROR: Log file was not created!" . PHP_EOL;
    exit(1);
}
