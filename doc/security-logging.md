# Security Logging

This API includes comprehensive security logging to track authentication, authorization, and sensitive operations.

## Log Location

Security logs are stored in: `logs/security.log`

## Log Rotation

- Maximum log file size: **10 MB**
- Backup files: **5 rotations** (security.log.1 through security.log.5)
- Automatic rotation when size limit is reached

## Logged Events

### Authentication Events

#### LOGIN_SUCCESS
Logged when a user successfully authenticates.
```json
{
  "level": "INFO",
  "event": "LOGIN_SUCCESS",
  "username": "john_doe",
  "user_id": 42,
  "ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "method": "POST",
  "uri": "/memory/login",
  "timestamp": "2026-01-05 14:30:00"
}
```

#### LOGIN_FAILURE
Logged when a login attempt fails.
```json
{
  "level": "WARNING",
  "event": "LOGIN_FAILURE",
  "username": "john_doe",
  "reason": "Invalid credentials",
  "ip": "192.168.1.100",
  "timestamp": "2026-01-05 14:30:00"
}
```

#### TOKEN_VALIDATION_FAILURE
Logged when JWT token validation fails.
```json
{
  "level": "WARNING",
  "event": "TOKEN_VALIDATION_FAILURE",
  "reason": "Expired token",
  "token_prefix": "eyJhbGciOiJIUzI1NiIs...",
  "ip": "192.168.1.100",
  "timestamp": "2026-01-05 14:30:00"
}
```

#### TOKEN_REFRESH
Logged when a user refreshes their access token.
```json
{
  "level": "INFO",
  "event": "TOKEN_REFRESH",
  "user_id": 42,
  "username": "john_doe",
  "timestamp": "2026-01-05 14:30:00"
}
```

### Authorization Events

#### AUTHORIZATION_FAILURE
Logged when a user attempts an action they don't have permission for.
```json
{
  "level": "WARNING",
  "event": "AUTHORIZATION_FAILURE",
  "resource": "user_modification",
  "action": "modify",
  "user_id": 42,
  "role": "developer",
  "timestamp": "2026-01-05 14:30:00"
}
```

### User Management Events

#### USER_CREATED
Logged when a new user account is created.
```json
{
  "level": "INFO",
  "event": "USER_CREATED",
  "user_id": 100,
  "username": "new_user",
  "created_by": 42,
  "timestamp": "2026-01-05 14:30:00"
}
```

#### USER_DELETED
Logged when a user account is deleted.
```json
{
  "level": "WARNING",
  "event": "USER_DELETED",
  "user_id": 100,
  "username": "deleted_user",
  "deleted_by": 42,
  "timestamp": "2026-01-05 14:30:00"
}
```

#### ROLE_CHANGED
Logged when a user's role is modified.
```json
{
  "level": "WARNING",
  "event": "ROLE_CHANGED",
  "user_id": 100,
  "username": "john_doe",
  "old_role": "developer",
  "new_role": "projectManager",
  "changed_by": 42,
  "timestamp": "2026-01-05 14:30:00"
}
```

#### PASSWORD_CHANGE
Logged when a user changes their password.
```json
{
  "level": "INFO",
  "event": "PASSWORD_CHANGE",
  "user_id": 42,
  "username": "john_doe",
  "timestamp": "2026-01-05 14:30:00"
}
```

#### PASSWORD_RESET
Logged when a password is reset (typically by admin).
```json
{
  "level": "INFO",
  "event": "PASSWORD_RESET",
  "user_id": 42,
  "username": "john_doe",
  "timestamp": "2026-01-05 14:30:00"
}
```

### Security Incidents

#### SUSPICIOUS_ACTIVITY
Logged for suspicious or anomalous behavior.
```json
{
  "level": "CRITICAL",
  "event": "SUSPICIOUS_ACTIVITY",
  "description": "Multiple failed attempts from same IP",
  "ip": "192.168.1.100",
  "timestamp": "2026-01-05 14:30:00"
}
```

#### BRUTE_FORCE_DETECTED
Logged when potential brute force attack is detected.
```json
{
  "level": "CRITICAL",
  "event": "BRUTE_FORCE_DETECTED",
  "username": "admin",
  "attempt_count": 10,
  "ip": "192.168.1.100",
  "timestamp": "2026-01-05 14:30:00"
}
```

#### RATE_LIMIT_EXCEEDED
Logged when a client exceeds rate limits.
```json
{
  "level": "WARNING",
  "event": "RATE_LIMIT_EXCEEDED",
  "endpoint": "/memory/notes",
  "user_id": 42,
  "ip": "192.168.1.100",
  "timestamp": "2026-01-05 14:30:00"
}
```

### Data Access Events

#### DATA_ACCESS
Logged for access to sensitive resources (optional, can be enabled as needed).
```json
{
  "level": "INFO",
  "event": "DATA_ACCESS",
  "resource": "user_profile",
  "resource_id": 42,
  "user_id": 50,
  "action": "VIEW",
  "timestamp": "2026-01-05 14:30:00"
}
```

## Log Levels

- **INFO**: Normal operations (successful login, data access)
- **WARNING**: Security concerns (failed login, authorization failure, user deletion)
- **CRITICAL**: Security incidents (brute force, suspicious activity)

## Log Format

All logs are in JSON format with the following standard fields:

- `level`: Log severity (INFO, WARNING, CRITICAL)
- `event`: Event type identifier
- `ip`: Client IP address (considers proxy headers)
- `user_agent`: Client user agent string
- `method`: HTTP method (GET, POST, etc.)
- `uri`: Request URI
- `timestamp`: Event timestamp (YYYY-MM-DD HH:MM:SS)

Additional fields vary by event type.

## Security Considerations

### IP Address Detection

The logger attempts to get the real client IP by checking:
1. `HTTP_CF_CONNECTING_IP` (Cloudflare)
2. `HTTP_X_FORWARDED_FOR` (Proxy)
3. `HTTP_X_REAL_IP` (Nginx)
4. `REMOTE_ADDR` (Direct connection)

### Token Logging

For security, only the first 20 characters of tokens are logged to prevent token exposure in logs.

### File Permissions

Log directory permissions are set to `0750` for security.

## Usage in Code

### Get Logger Instance
```php
$logger = SecurityLogger::getInstance();
```

### Log Events
```php
// Successful login
$logger->logLoginSuccess($username, $userId);

// Failed login
$logger->logLoginFailure($username, 'Invalid credentials');

// Authorization failure
$logger->logAuthorizationFailure('resource_name', 'action', $userId, $role);

// User creation
$logger->logUserCreation($userId, $username, $createdBy);

// User deletion
$logger->logUserDeletion($userId, $username, $deletedBy);

// Role change
$logger->logRoleChange($userId, $username, $oldRole, $newRole, $changedBy);

// Password change
$logger->logPasswordChange($userId, $username, $isReset = false);

// Token validation failure
$logger->logTokenValidationFailure($reason, $token);

// Token refresh
$logger->logTokenRefresh($userId, $username);

// Suspicious activity
$logger->logSuspiciousActivity($description, $additionalData);

// Brute force detection
$logger->logBruteForceAttempt($username, $attemptCount);

// Rate limiting
$logger->logRateLimitExceeded($endpoint, $userId);

// Data access
$logger->logDataAccess($resource, $resourceId, $userId, $action);
```

## Monitoring and Alerts

### Recommended Monitoring

1. **Failed Login Attempts**: Monitor for patterns indicating brute force attacks
2. **Authorization Failures**: Track unauthorized access attempts
3. **Token Validation Failures**: Detect token tampering or replay attacks
4. **User Deletions**: Audit trail for account removals
5. **Role Changes**: Track privilege escalations

### Log Analysis Tools

You can use various tools to analyze the JSON logs:

```bash
# Count failed login attempts
grep "LOGIN_FAILURE" logs/security.log | wc -l

# Find all events from a specific IP
grep "192.168.1.100" logs/security.log

# Get all CRITICAL events
grep '"level":"CRITICAL"' logs/security.log

# Pretty print recent events
tail -n 100 logs/security.log | jq .

# Count events by type
grep -o '"event":"[^"]*"' logs/security.log | sort | uniq -c
```

### Integration with SIEM

The JSON format makes it easy to integrate with SIEM systems like:
- Splunk
- ELK Stack (Elasticsearch, Logstash, Kibana)
- Graylog
- Sumo Logic

## Compliance

This logging implementation helps meet compliance requirements for:
- **GDPR**: Audit trail of data access and modifications
- **HIPAA**: Security event tracking
- **SOC 2**: Access control and monitoring
- **PCI DSS**: Authentication and access logging

## Best Practices

1. **Regular Review**: Review security logs regularly for anomalies
2. **Retention Policy**: Establish log retention policies based on compliance needs
3. **Secure Storage**: Ensure logs are stored securely and backed up
4. **Access Control**: Restrict access to security logs
5. **Alert Configuration**: Set up alerts for CRITICAL events
6. **Log Integrity**: Consider implementing log signing for tamper detection
