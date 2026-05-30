# 🚀 KTX Management System - Quick Start Guide

**Status**: ✅ **READY TO USE**

---

## **📊 System Setup Verification**

### ✅ Database Status
- **Database Name**: `ktx`
- **Tables Created**: 12 (buildings, contracts, invoices, maintenance_requests, notifications, rooms, room_amenities, room_registrations, students, users, utility_readings, violation_records)
- **Sample Data**: ✅ Seeded and Ready
- **Connection**: ✅ Verified

### ✅ Application Files
- **Location**: `C:\xampp\htdocs\testfinal`
- **Entry Point**: `public/index.php`
- **Framework**: PHP MVC (Custom)
- **PHP Version**: 7.4+

### ✅ Server Configuration
- **Web Server**: Apache (via XAMPP)
- **Database**: MySQL/MariaDB
- **URL Rewriting**: `.htaccess` configured

---

## **🚀 HOW TO RUN THE APPLICATION**

### **Step 1: Start Services**

#### **Option A: XAMPP Control Panel (Easiest)**
```
1. Open: C:\xampp\xampp-control.exe
2. Click "Start" button next to Apache
3. Click "Start" button next to MySQL
4. Wait until both show "Running"
```

#### **Option B: Windows Services (PowerShell)**
```powershell
# Start Apache
Start-Service -Name "Apache2.4"

# Start MySQL
Start-Service -Name "MySQL80"

# Verify services are running
Get-Service Apache2.4, MySQL80 | Format-Table Name, Status
```

---

### **Step 2: Access Application**

#### **Home Page**
```
URL: http://localhost/testfinal/public/
```

#### **Login Page**
```
URL: http://localhost/testfinal/public/auth/login
```

---

## **👤 Test Credentials**

### **Admin Account**
```
Username: admin
Password: admin123
Role: Administrator
```

### **Student Accounts**
```
Username: student01 (or sv001)
Password: student123
Role: Student

Username: student02 (or sv002)  
Password: student123
Role: Student
```

---

## **✨ Key Features Available**

### **👨‍💼 Admin Features**
- ✅ Dashboard with statistics
- ✅ User management (add/edit/delete students)
- ✅ Room management and allocation
- ✅ Billing and invoice generation
- ✅ Violation tracking and management
- ✅ Maintenance request handling
- ✅ Reports and analytics

### **👨‍🎓 Student Features**
- ✅ View personal profile
- ✅ View allocated room
- ✅ Download invoices
- ✅ View violation records
- ✅ Check billing status

---

## **🧪 Testing Checklist**

### **After Login as Admin**

```
□ Dashboard loads successfully
□ See total users, rooms, and violations
□ Click "Users" → View student list
□ Click on a student → View details
□ Click "Rooms" → View room list
□ Click "Billing" → View invoices
□ Click "Violations" → View violations list
□ Navigate menu items without errors
```

### **After Login as Student**

```
□ Dashboard loads successfully
□ See personal information
□ See room allocation (if any)
□ Navigate to Invoices page
□ View or download invoice
□ Navigate to Violations page (if any)
□ Logout successfully
```

---

## **📁 File Structure**

```
C:\xampp\htdocs\testfinal/
├── public/
│   ├── index.php              ← Entry point (visit first)
│   └── .htaccess              ← URL rewriting rules
├── app/
│   ├── core/                  ← Framework core classes
│   ├── controllers/           ← Business logic
│   ├── models/                ← Database models
│   ├── services/              ← Complex operations
│   └── views/                 ← HTML templates
├── config/
│   └── config.php             ← Configuration (DB credentials, etc)
├── middleware/                ← Authentication & authorization
├── routes/
│   └── web.php                ← URL routes
├── test/
│   └── database_demo.php      ← Sample data generator
├── ktx.sql                    ← Database schema
├── SETUP_GUIDE.md             ← Detailed setup instructions
├── IMPLEMENTATION_GUIDE.md    ← Complete implementation guide
├── QUICK_START.md             ← This file
└── setup.bat                  ← Windows batch setup script
```

---

## **⚙️ Database Configuration**

If you need to **change database credentials**, edit:
```
File: C:\xampp\htdocs\testfinal\config\config.php

Lines to modify:
define('DB_HOST', '127.0.0.1');    // MySQL host
define('DB_USER', 'root');         // MySQL username
define('DB_PASS', '');             // MySQL password
define('DB_NAME', 'ktx');          // Database name
```

---

## **🔧 Troubleshooting**

### ❌ **"Cannot connect to database"**
```powershell
# Check MySQL is running
Get-Service MySQL80 | Select Status

# Start MySQL if stopped
Start-Service MySQL80

# Verify database exists
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW DATABASES;"
```

### ❌ **"404 Page Not Found"**
```
1. Check Apache is running
2. Clear browser cache (Ctrl+Shift+Delete)
3. Verify URL: http://localhost/testfinal/public/
4. Restart Apache in XAMPP Control Panel
```

### ❌ **"Blank white page"**
```
1. Enable debug mode in config.php:
   define('APP_DEBUG', true);
   
2. Check error logs:
   - Apache: C:\xampp\apache\logs\error.log
   - PHP: C:\xampp\php\php_error.log
   
3. Run syntax check:
   php -l C:\xampp\htdocs\testfinal\public\index.php
```

### ❌ **"Database tables not found"**
```powershell
# Re-import database schema
& "C:\xampp\mysql\bin\mysql.exe" -u root < "C:\xampp\htdocs\testfinal\ktx.sql"

# Re-run sample data seeder
cd C:\xampp\htdocs\testfinal
php test/database_demo.php
```

---

## **📚 Documentation Files**

1. **QUICK_START.md** (This File)
   - Quick overview and getting started

2. **SETUP_GUIDE.md**
   - Detailed step-by-step setup instructions
   - Environment verification
   - Database configuration
   - Troubleshooting guide

3. **IMPLEMENTATION_GUIDE.md**
   - Complete feature documentation
   - Testing checklist
   - API endpoints
   - Database schema details
   - Common commands

---

## **🎯 Common Tasks**

### **Stop Services**
```powershell
# Stop Apache
Stop-Service -Name "Apache2.4"

# Stop MySQL
Stop-Service -Name "MySQL80"
```

### **Backup Database**
```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" -u root ktx > "ktx_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"
```

### **Restore Database**
```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root ktx < "ktx_backup_20260530_120000.sql"
```

### **Clear Sample Data & Start Fresh**
```powershell
# Drop and recreate database
& "C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS ktx;"
& "C:\xampp\mysql\bin\mysql.exe" -u root < "C:\xampp\htdocs\testfinal\ktx.sql"

# Re-seed with sample data
cd C:\xampp\htdocs\testfinal
php test/database_demo.php
```

---

## **✅ Verification Checklist**

- [x] Database created (`ktx`)
- [x] 12 tables created
- [x] Sample data seeded
- [x] PHP files syntax verified
- [x] Configuration file exists
- [x] Entry point accessible
- [x] Apache rewrite rules configured
- [x] MySQL connection working

---

## **🚨 Important Notes**

1. **Do NOT commit `config/config.php`** to Git (it contains credentials)
   - Add to `.gitignore` if not already there

2. **Keep MySQL running** while using the application
   - The app will fail if database is unavailable

3. **Use the provided test credentials** for testing
   - Don't create random accounts without verifying the registration system

4. **Backup important data** before making changes
   - Use the mysqldump command provided above

5. **Clear browser cache** if you see old pages or styling issues
   - Press: Ctrl + Shift + Delete

---

## **📞 Support**

For more detailed information, refer to:
- **SETUP_GUIDE.md** - Detailed setup steps
- **IMPLEMENTATION_GUIDE.md** - Complete feature documentation

---

## **🎉 You're All Set!**

The KTX Management System is ready to use. 

**Next Steps:**
1. Start XAMPP services
2. Open: `http://localhost/testfinal/public/`
3. Login with admin or student credentials
4. Explore the features!

---

**System Status**: ✅ Ready for Development/Testing  
**Last Updated**: 2026-05-30  
**Created by**: KTX Management System Team
