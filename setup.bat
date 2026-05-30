@echo off
REM ====================================================
REM KTX Management System - Windows Setup Batch Script
REM ====================================================

setlocal enabledelayedexpansion

echo.
echo ===================================================
echo KTX Management System - Automated Setup
echo ===================================================
echo.

cd /d C:\xampp\htdocs\testfinal

REM === STEP 1: Import Database ===
echo [STEP 1] Importing Database Schema...
mysql -u root < ktx.sql
if %errorlevel% equ 0 (
    echo [OK] Database schema imported successfully
) else (
    echo [ERROR] Database import failed
    echo Check if MySQL is running and credentials are correct
    pause
    exit /b 1
)

REM === STEP 2: Verify Database ===
echo.
echo [STEP 2] Verifying Database...
mysql -u root -e "SELECT COUNT(*) as 'Total Tables' FROM information_schema.tables WHERE table_schema='ktx';"

REM === STEP 3: Seed Sample Data ===
echo.
echo [STEP 3] Creating Sample Data...
php test/database_demo.php
if %errorlevel% equ 0 (
    echo [OK] Sample data created successfully
) else (
    echo [ERROR] Sample data creation had issues
)

REM === STEP 4: Verify PHP Syntax ===
echo.
echo [STEP 4] Checking PHP Syntax...
php -l public/index.php
php -l config/config.php

REM === STEP 5: Display Summary ===
echo.
echo ===================================================
echo [OK] Setup Completed Successfully!
echo ===================================================
echo.
echo NEXT STEPS:
echo -----------
echo 1. Start XAMPP (Apache + MySQL)
echo 2. Open browser: http://localhost/testfinal/public/
echo 3. Login as Admin:
echo    Username: admin
echo    Password: admin123
echo.
echo 4. Or login as Student:
echo    Username: student01
echo    Password: student123
echo.
echo Documentation:
echo - Setup Guide: SETUP_GUIDE.md
echo - Implementation Guide: IMPLEMENTATION_GUIDE.md
echo.

pause
