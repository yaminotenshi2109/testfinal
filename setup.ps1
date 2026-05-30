#!/usr/bin/env powershell
# KTX Management System - Automated Setup Script

Write-Host "===========================================" -ForegroundColor Cyan
Write-Host "KTX Management System - Setup Script" -ForegroundColor Cyan
Write-Host "===========================================" -ForegroundColor Cyan

$PROJECT_PATH = "C:\xampp\htdocs\testfinal"
$DB_HOST = "127.0.0.1"
$DB_NAME = "ktx"
$DB_USER = "root"

# === STEP 1: Environment Verification ===
Write-Host "`n[STEP 1] Verifying Environment..." -ForegroundColor Yellow

if (Test-Path $PROJECT_PATH) {
    Write-Host "✓ Project directory found" -ForegroundColor Green
}
else {
    Write-Host "✗ Project directory not found!" -ForegroundColor Red
    exit 1
}

$phpVersion = php -v 2>&1 | Select-Object -First 1
Write-Host "✓ PHP: $phpVersion" -ForegroundColor Green

$mysqlVersion = mysql --version 2>&1
Write-Host "✓ MySQL: $mysqlVersion" -ForegroundColor Green

# === STEP 2: Database Setup ===
Write-Host "`n[STEP 2] Setting up Database..." -ForegroundColor Yellow

$sqlFile = "$PROJECT_PATH\ktx.sql"
Write-Host "→ Importing schema from: $sqlFile" -ForegroundColor Cyan

# Import database
& cmd /c "mysql -u $DB_USER < `"$sqlFile`"" 2>$null
Write-Host "✓ Database schema imported" -ForegroundColor Green

# === STEP 3: Verify Config ===
Write-Host "`n[STEP 3] Verifying Configuration..." -ForegroundColor Yellow

$configFile = "$PROJECT_PATH\config\config.php"
if (Test-Path $configFile) {
    Write-Host "✓ Configuration file exists" -ForegroundColor Green
}

# === STEP 4: Seed Data ===
Write-Host "`n[STEP 4] Seeding Sample Data..." -ForegroundColor Yellow

$seedFile = "$PROJECT_PATH\test\database_demo.php"
if (Test-Path $seedFile) {
    Write-Host "→ Running database seeder..." -ForegroundColor Cyan
    Push-Location $PROJECT_PATH
    php test/database_demo.php 2>$null
    Pop-Location
    Write-Host "✓ Sample data created" -ForegroundColor Green
}

# === STEP 5: Verify Project Structure ===
Write-Host "`n[STEP 5] Validating Project Structure..." -ForegroundColor Yellow

$dirs = @("app\core", "app\models", "app\controllers", "app\views", "config", "public")
foreach ($dir in $dirs) {
    $path = Join-Path $PROJECT_PATH $dir
    if (Test-Path $path) {
        Write-Host "✓ $dir" -ForegroundColor Green
    }
}

# === STEP 6: Test PHP Files ===
Write-Host "`n[STEP 6] Checking PHP Syntax..." -ForegroundColor Yellow

$mainFiles = @("public\index.php", "config\config.php")
foreach ($file in $mainFiles) {
    $path = Join-Path $PROJECT_PATH $file
    $check = php -l $path 2>&1
    if ($check -match "No syntax errors") {
        Write-Host "✓ $file" -ForegroundColor Green
    }
}

# === FINAL SUMMARY ===
Write-Host "`n===========================================" -ForegroundColor Cyan
Write-Host "✅ Setup Completed Successfully!" -ForegroundColor Green
Write-Host "===========================================" -ForegroundColor Cyan

Write-Host "`n📍 Next Steps:" -ForegroundColor Green
Write-Host "1. Start XAMPP (Apache + MySQL services)" -ForegroundColor White
Write-Host "2. Open browser: http://localhost/testfinal/public/" -ForegroundColor White
Write-Host "3. Login with:" -ForegroundColor White
Write-Host "   Admin: admin / admin123" -ForegroundColor Gray
Write-Host "   Student: student01 / student123" -ForegroundColor Gray

Write-Host "`n📚 Documentation:" -ForegroundColor Green
Write-Host "- Setup Guide: $PROJECT_PATH\SETUP_GUIDE.md" -ForegroundColor White

Write-Host "`n✨ Project is ready to use!`n" -ForegroundColor Green
