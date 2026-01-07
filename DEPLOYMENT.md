# Memory API - Deployment Guide

This guide explains how to deploy the Memory API to a production server using the automated FTP deployment script.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Initial Setup](#initial-setup)
- [Deployment Process](#deployment-process)
- [Post-Deployment Steps](#post-deployment-steps)
- [Backup Management](#backup-management)
- [Troubleshooting](#troubleshooting)

## Prerequisites

### Local Machine Requirements

1. **PHP 7.4+** with FTP extension (usually enabled by default)
2. **Composer** for dependency management
3. **Git** (for version control)

#### Verifying PHP FTP Extension

The FTP extension is typically enabled by default in PHP installations. To verify:

```bash
php -m | grep ftp
```

If not enabled, add to your `php.ini`:
```ini
extension=ftp
```

**On Windows:**
Uncomment `;extension=ftp` in php.ini

**On Ubuntu/Debian:**
```bash
sudo apt-get install php-ftp
```

**On macOS:**
FTP is usually included by default.

### Remote Server Requirements

1. **PHP 7.4+** with required extensions:
   - PDO
   - pdo_mysql
   - mysqli
   - zip

2. **MySQL 8.0+** database server

3. **FTP access** with password authentication

4. **Apache web server** with:
   - mod_rewrite enabled
   - .htaccess support

5. **Writable directories** for:
   - `uploads/` - User file storage
   - `logs/` - Application logs

## Initial Setup

### 1. Configure Deployment Credentials

Copy the deployment credentials template:

```bash
cp .env.deploy.example .env.deploy
```

Edit `.env.deploy` with your server details:

```env
FTP_HOST=your-server.example.com
FTP_PORT=21
FTP_USER=your-username
FTP_PASSWORD=your-password
FTP_PASSIVE=true
FTP_REMOTE_PATH=/var/www/html/memory
BACKUP_LOCATION=sibling
BACKUP_KEEP_COUNT=5
```

**Important:** Never commit `.env.deploy` to version control!

### 2. Verify Deployment Configuration

Review `.deploy-config.json` to ensure all necessary files are included/excluded according to your needs.

### 3. Test FTP Connection

Test your credentials with a dry run:

```bash
php deploy-ftp.php --dry-run
```

This will preview what would be deployed without making any changes.

## Deployment Process

### Standard Deployment

Deploy to production:

```bash
php deploy-ftp.php
```

The script will:

1. ✅ Build vendor directory with production dependencies
2. ✅ Connect to FTP server
3. ✅ Create timestamped backup of current deployment
4. ✅ Upload all application files
5. ✅ Create required directories (uploads/, logs/)
6. ✅ Clean up old backups (keeps last 5 by default)

### Dry Run Mode

Preview deployment without making changes:

```bash
php deploy-ftp.php --dry-run
```

Use this to:
- Verify connection settings
- Review which files will be uploaded
- Check backup locations
- Test before actual deployment

### Deployment Script Options

```bash
php deploy-ftp.php          # Standard deployment
php deploy-ftp.php --dry-run # Preview mode
php deploy-ftp.php --help    # Show help
```

## Post-Deployment Steps

After successful deployment, complete these steps on the server:

### 1. Create Production Environment File

SSH into your server and create `.env` file:

```bash
cd /var/www/html/memory
nano .env
```

Use `.env.production.example` as a template. Configure:

```env
# Database
DB_HOST=127.0.0.1
DB_USERNAME=production_user
DB_PASSWORD=strong_password_here
DB_DATABASE=memory_production

# JWT Authentication
JWT_SECRET_KEY=generate_a_very_strong_secret_key_at_least_256_bits
JWT_ALGORITHM=HS256
JWT_EXPIRATION_TIME=3600
JWT_ISSUER=memory-api
JWT_AUDIENCE=memory-api-users

# Application
APP_ENV=production
```

**Generate strong JWT secret:**
```bash
openssl rand -base64 64
```

### 2. Set File Permissions

Set appropriate permissions for security:

```bash
# Application files (read-only for web server)
chmod 755 Controllers/ Models/ inc/
chmod 644 index.php .htaccess

# Writable directories
chmod 750 uploads/ logs/
chown -R www-data:www-data uploads/ logs/

# Secure .env file
chmod 600 .env
```

### 3. Update CORS Settings

Edit `index.php` to set your production domain:

```php
// Change this line:
header("Access-Control-Allow-Origin: http://localhost:3000");

// To your production domain:
header("Access-Control-Allow-Origin: https://your-domain.com");
```

Or for multiple origins, implement dynamic CORS:

```php
$allowedOrigins = [
    'https://your-domain.com',
    'https://www.your-domain.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
```

### 4. Create Database and Import Schema

Create the database:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE memory_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'memory_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON memory_production.* TO 'memory_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Import SQL schema (in order):

```bash
cd /var/www/html/memory/SQL
mysql -u memory_user -p memory_production < tables.sql
mysql -u memory_user -p memory_production < functions.sql
mysql -u memory_user -p memory_production < views.sql
mysql -u memory_user -p memory_production < utility-views.sql
mysql -u memory_user -p memory_production < triggers.sql
mysql -u memory_user -p memory_production < insert.sql  # Optional: sample data
```

### 5. Verify Installation

Test the API endpoint:

```bash
curl https://your-domain.com/memory/
```

You should see a response from the API.

Test login endpoint:

```bash
curl -X POST https://your-domain.com/memory/login \
  -H "Content-Type: application/json" \
  -d '{"username":"your_username","password":"your_password"}'
```

### 6. Disable Debug Mode

Ensure error display is disabled in production.

Check `index.php` - this line should be commented out or removed:

```php
// ini_set('display_errors', 1);
```

### 7. Verify File Upload Security

Check that `uploads/.htaccess` is properly deployed:

```bash
cat /var/www/html/memory/uploads/.htaccess
```

It should contain:
```apache
Order deny,allow
Deny from all

<FilesMatch "\.(jpg|jpeg|png|gif|pdf|doc|docx|xls|xlsx)$">
    Deny from all
</FilesMatch>

php_flag engine off
```

## Backup Management

### Viewing Backups

Backups are stored according to your `BACKUP_LOCATION` setting:

**Sibling directory mode:**
```
/var/www/html/
├── memory/                      # Live application
├── memory_backup_20260107_143022/
├── memory_backup_20260107_120530/
└── memory_backup_20260106_093045/
```

**Backups directory mode:**
```
/var/www/html/
├── memory/                      # Live application
└── backups/
    ├── memory_backup_20260107_143022/
    ├── memory_backup_20260107_120530/
    └── memory_backup_20260106_093045/
```

### Manual Rollback

To restore from a backup:

```bash
cd /var/www/html

# Stop web server (optional, for safety)
sudo systemctl stop apache2

# Backup current state (just in case)
mv memory memory_broken_$(date +%Y%m%d_%H%M%S)

# Restore from backup
cp -r memory_backup_20260107_143022 memory

# Start web server
sudo systemctl start apache2
```

### Cleanup Old Backups

The deployment script automatically keeps the most recent backups based on `BACKUP_KEEP_COUNT` setting.

To manually remove old backups:

```bash
# List backups by date
ls -lt /var/www/html/memory_backup_*

# Remove specific backup
rm -rf /var/www/html/memory_backup_20260101_120000
```

## Troubleshooting

### FTP Extension Not Found

**Error:** `PHP FTP extension is not installed`

**Solution:**

**On Windows:**
Edit php.ini and uncomment:
```ini
extension=ftp
```

**On Ubuntu/Debian:**
```bash
sudo apt-get install php-ftp
sudo systemctl restart apache2
```

**On macOS:**
FTP is usually enabled by default. Check php.ini if needed.

### FTP Connection Failed

**Error:** `Failed to connect to FTP server`

**Possible causes:**
1. **Incorrect host/port:** Verify `FTP_HOST` and `FTP_PORT` in `.env.deploy`
2. **Firewall blocking:** Ensure port 21 is open
3. **Passive mode issues:** Try toggling `FTP_PASSIVE` between true/false
4. **Server down:** Check if server is accessible

**Test connection manually:**
```bash
ftp your-server.example.com
```

### Authentication Failed

**Error:** `FTP authentication failed`

**Solutions:**
1. Verify username and password in `.env.deploy`
2. Check if password contains special characters that need escaping
3. Ensure FTP account has proper permissions
4. Verify FTP user has write access to the target directory

### Permission Denied on Remote Server

**Error:** Files upload but can't be written

**Solution:**
```bash
# Check directory ownership
ls -la /var/www/html/

# Fix permissions
sudo chown -R www-data:www-data /var/www/html/memory
sudo chmod 750 /var/www/html/memory/uploads
sudo chmod 750 /var/www/html/memory/logs
```

### Composer Install Failed

**Error:** `Composer install failed`

**Solutions:**
1. Ensure Composer is installed: `composer --version`
2. Check if `composer.json` exists
3. Delete `vendor/` directory and try again:
   ```bash
   rm -rf vendor/
   composer install --no-dev --optimize-autoloader
   ```

### Upload Interrupted

If deployment is interrupted:

1. **Check partial upload:** Files may be partially deployed
2. **Restore from backup:** Use the most recent backup
3. **Re-run deployment:** The script will overwrite existing files

### Database Import Errors

**Error:** SQL import fails

**Solutions:**
1. Import files in correct order (see Post-Deployment Steps)
2. Check MySQL user permissions
3. Verify database character set: `utf8mb4`
4. Check for existing tables:
   ```sql
   SHOW TABLES;
   DROP TABLE IF EXISTS table_name;  # If needed
   ```

### 500 Internal Server Error

**Possible causes:**

1. **Missing .env file**
   ```bash
   ls -la /var/www/html/memory/.env
   ```

2. **Incorrect file permissions**
   ```bash
   chmod 644 index.php
   chmod 755 Controllers/ Models/ inc/
   ```

3. **mod_rewrite not enabled**
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

4. **PHP errors** - Check Apache error log:
   ```bash
   tail -f /var/log/apache2/error.log
   ```

### CORS Errors

**Error:** Frontend can't connect to API

**Solution:** Update CORS headers in `index.php`:

```php
header("Access-Control-Allow-Origin: https://your-frontend-domain.com");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
```

### File Upload Not Working

**Checklist:**
1. ✅ `uploads/` directory exists and is writable
2. ✅ `uploads/.htaccess` is present
3. ✅ Directory permissions are 750
4. ✅ Web server user owns the directory

```bash
ls -la uploads/
cat uploads/.htaccess
```

## Best Practices

1. **Always test with `--dry-run` first** before deploying to production

2. **Use passive mode:** Keep `FTP_PASSIVE=true` for better compatibility with firewalls

3. **Keep backups:** Don't set `BACKUP_KEEP_COUNT` to 0 in production

4. **Deploy during low-traffic periods** to minimize user impact

5. **Monitor logs after deployment:**
   ```bash
   tail -f /var/www/html/memory/logs/security.log
   tail -f /var/log/apache2/error.log
   ```

6. **Use environment-specific configuration:**
   - Development: `.env` with debug enabled
   - Production: `.env` with debug disabled

6. **Document changes:** Keep track of deployments and configuration changes

7. **Test critical endpoints** after deployment:
   - Login
   - Note creation
   - File upload
   - User authentication

## Security Checklist

After deployment, verify:

- [ ] `.env` file has restricted permissions (600)
- [ ] Error display is disabled in production
- [ ] `uploads/.htaccess` prevents direct access to files
- [ ] Database user has minimal required privileges
- [ ] JWT secret key is strong and unique
- [ ] CORS is restricted to your domain only
- [ ] HTTPS is enabled (SSL certificate installed)
- [ ] File upload size limits are configured
- [ ] logs/ directory is not publicly accessible

## Support

For issues not covered in this guide:

1. Check application logs: `/var/www/html/memory/logs/security.log`
2. Check Apache logs: `/var/log/apache2/error.log`
3. Review deployment script output for errors
4. Verify all post-deployment steps were completed

## Additional Resources

- [README.md](README.md) - Project overview and local setup
- [TESTING.md](TESTING.md) - Testing procedures
- `.env.production.example` - Production environment template
- `.deploy-config.json` - Deployment configuration reference
