@echo off
REM Automated Database Backup Script for Windows Task Scheduler
REM This script runs the PHP backup and logs the output

REM Set paths (adjust according to your WAMP installation)
set PHP_PATH=C:\wamp64\bin\php\php8.2.0\php.exe
set SCRIPT_PATH=%~dp0backup-database.php
set LOG_PATH=%~dp0logs\backup-%date:~-4,4%%date:~-7,2%%date:~-10,2%.log

REM Create logs directory if it doesn't exist
if not exist "%~dp0logs\" mkdir "%~dp0logs\"

REM Run backup and log output
echo [%date% %time%] Starting database backup... >> "%LOG_PATH%"
"%PHP_PATH%" "%SCRIPT_PATH%" >> "%LOG_PATH%" 2>&1

REM Check if backup was successful
if %ERRORLEVEL% EQU 0 (
    echo [%date% %time%] Backup completed successfully >> "%LOG_PATH%"
) else (
    echo [%date% %time%] Backup failed with error code %ERRORLEVEL% >> "%LOG_PATH%"
)

echo. >> "%LOG_PATH%"
