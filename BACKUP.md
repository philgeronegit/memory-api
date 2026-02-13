# Database Backup & Restore Guide

## Overview

This project includes automated database backup and restore scripts to ensure data safety and recovery capabilities.

## Files

- `backup-database.php` - Main backup script
- `backup-schedule.bat` - Windows Task Scheduler script
- `restore-database.php` - Database restore script
- `backups/` - Directory for backup files (auto-created)

## Quick Start

### Manual Backup

```bash
php backup-database.php
```

### Manual Backup with FTP Upload

```bash
php backup-database.php --upload-ftp
```

### Restore from Backup

```bash
php restore-database.php backups/memory_backup_2026-01-18_14-30-00.sql.gz
```

## Automated Backups (Windows)

### Setup Task Scheduler

1. Open **Task Scheduler** (search in Windows Start menu)
2. Click **Create Basic Task**
3. Configure:
   - **Name**: Memory Database Backup
   - **Trigger**: Daily at 2:00 AM
   - **Action**: Start a program
   - **Program**: `C:\wamp64\www\memory\backup-schedule.bat`
   - **Start in**: `C:\wamp64\www\memory`

### Verify Scheduled Task

Check logs in `logs/backup-YYYYMMDD.log`

## Automated Backups (Linux/Mac)

Add to crontab:

```bash
# Daily backup at 2 AM
0 2 * * * cd /path/to/memory && php backup-database.php >> logs/backup.log 2>&1

# Weekly backup with FTP upload (Sundays at 3 AM)
0 3 * * 0 cd /path/to/memory && php backup-database.php --upload-ftp >> logs/backup.log 2>&1
```

## Configuration

### Environment Variables (.env)

Required variables (already in your .env):
```
DB_HOST=localhost
DB_USERNAME=your_user
DB_PASSWORD=your_password
DB_DATABASE=memory_db
```

Optional for FTP upload:
```
FTP_HOST=ftp.yourserver.com
FTP_USER=your_ftp_user
FTP_PASSWORD=your_ftp_password
```

### Backup Settings

Edit in `backup-database.php`:
- `$maxLocalBackups = 30` - Number of backups to keep locally
- `$backupDir` - Backup directory location

### MySQL Path Configuration

If using custom MySQL installation, update in scripts:

**Windows:**
```php
$mysqlDumpPath = 'C:\\wamp64\\bin\\mysql\\mysql8.2.0\\bin\\mysqldump.exe';
$mysqlPath = 'C:\\wamp64\\bin\\mysql\\mysql8.2.0\\bin\\mysql.exe';
```

**And in backup-schedule.bat:**
```batch
set PHP_PATH=C:\wamp64\bin\php\php8.2.0\php.exe
```

## Backup Features

- ✅ Automatic compression (gzip)
- ✅ Timestamped filenames
- ✅ Automatic rotation (keeps last 30 backups)
- ✅ Includes stored procedures, triggers, and routines
- ✅ Transaction-safe backup (--single-transaction)
- ✅ Optional FTP upload
- ✅ Detailed logging
- ✅ Error handling

## Backup File Naming

Format: `memory_backup_YYYY-MM-DD_HH-MM-SS.sql.gz`

Example: `memory_backup_2026-01-18_14-30-00.sql.gz`

## Backup Size

Typical sizes:
- Uncompressed SQL: ~10-50 MB (depending on data)
- Compressed (gzip): ~2-10 MB (70-90% reduction)

## Restore Process

1. **Verify backup file exists**
   ```bash
   ls -lh backups/
   ```

2. **Run restore script**
   ```bash
   php restore-database.php backups/memory_backup_2026-01-18_14-30-00.sql.gz
   ```

3. **Confirm restoration**
   - Type `yes` when prompted
   - Script will decompress and restore automatically

4. **Verify data**
   - Check database via phpMyAdmin
   - Test API endpoints

## Best Practices

### Regular Testing
- **Monthly**: Test restore process on development environment
- **Quarterly**: Verify backup file integrity

### Multiple Backup Locations
1. **Local**: `backups/` directory (30 days)
2. **Remote**: FTP server (weekly uploads)
3. **Manual**: Monthly export via phpMyAdmin to external storage

### Before Major Changes
Always create a manual backup before:
- Database schema migrations
- Large data imports/updates
- Production deployments

```bash
php backup-database.php --upload-ftp
```

### Monitor Backup Logs
```bash
# View recent backup logs (Windows)
type logs\backup-*.log | more

# Linux/Mac
tail -f logs/backup.log
```

## Troubleshooting

### "mysqldump: command not found"
Update the `$mysqlDumpPath` variable in `backup-database.php` with the full path to mysqldump.

**Windows:**
```php
$mysqlDumpPath = 'C:\\wamp64\\bin\\mysql\\mysql8.2.0\\bin\\mysqldump.exe';
```

### "Access denied for user"
Verify database credentials in `.env` file.

### Backup file is empty
Check MySQL user has SELECT, LOCK TABLES, and SHOW VIEW privileges:
```sql
GRANT SELECT, LOCK TABLES, SHOW VIEW ON memory_db.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

### FTP upload fails
1. Verify FTP credentials in `.env`
2. Check firewall allows FTP connection
3. Verify FTP server `/backups/` directory exists

### Disk space issues
Reduce `$maxLocalBackups` or manually clean old backups:
```bash
# Keep only last 7 days
find backups/ -name "*.sql.gz" -mtime +7 -delete
```

## Manual phpMyAdmin Backup

For one-time backups or verification:

1. Open phpMyAdmin
2. Select `memory_db` database
3. Click **Export** tab
4. Choose:
   - Method: **Custom**
   - Format: **SQL**
   - Compression: **gzip**
   - Object creation options: ✓ All checkboxes
5. Click **Go**

## Recovery Scenarios

### Scenario 1: Accidental Data Deletion
```bash
# Find recent backup
ls -lt backups/ | head -5

# Restore
php restore-database.php backups/memory_backup_2026-01-18_14-30-00.sql.gz
```

### Scenario 2: Corrupted Database
```bash
# Use oldest stable backup
php restore-database.php backups/memory_backup_2026-01-17_02-00-00.sql.gz
```

### Scenario 3: Migration to New Server
1. Copy backup file to new server
2. Configure `.env` with new database credentials
3. Run restore script

## Security Considerations

- ✅ Backup files contain sensitive data - keep secure
- ✅ `.gitignore` prevents committing backups to Git
- ✅ Restrict access to `backups/` directory (chmod 700)
- ✅ Use encrypted FTP (FTPS/SFTP) for remote uploads
- ✅ Passwords not exposed in command line (passed via environment)
- ✅ Logs don't contain passwords

## Backup Verification

Create a test restore script:

```bash
# test-restore.sh
#!/bin/bash
LATEST_BACKUP=$(ls -t backups/*.sql.gz | head -1)
echo "Testing restore of: $LATEST_BACKUP"

# Create test database
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS memory_test"

# Restore to test database
zcat $LATEST_BACKUP | mysql -u root -p memory_test

# Verify table count
TABLE_COUNT=$(mysql -u root -p -D memory_test -e "SHOW TABLES" | wc -l)
echo "Restored $TABLE_COUNT tables"

# Cleanup
mysql -u root -p -e "DROP DATABASE memory_test"
```

## Support

For issues or questions:
1. Check logs in `logs/backup-*.log`
2. Verify environment variables in `.env`
3. Test MySQL connection manually
4. Review this documentation

---

**Last Updated**: January 18, 2026
