# 🚀 KTX Management System - Complete Implementation Guide

## **Quick Start Summary (5 Steps)**

```
1. Import Database Schema
2. Verify Configuration
3. Seed Sample Data
4. Start Web Server
5. Access Application
```

---

## **DETAILED STEP-BY-STEP IMPLEMENTATION**

### **Step 1️⃣: Import Database Schema**

#### **Option A: Using MySQL Command Line**
```powershell
# Open PowerShell and run:
mysql -u root < C:\xampp\htdocs\testfinal\ktx.sql
```

#### **Option B: Using MySQL Workbench or phpMyAdmin**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click **"New"** to create database
3. Database name: `ktx`
4. Charset: `utf8mb4_unicode_ci`
5. Click **"Create"**
6. Go to **"SQL"** tab
7. Copy-paste contents of `C:\xampp\htdocs\testfinal\ktx.sql`
8. Click **"Execute"**

#### **Verify Database Created:**
```powershell
mysql -u root -e "SHOW DATABASES LIKE 'ktx';"
mysql -u root -e "USE ktx; SHOW TABLES;"
```

**Expected Output**: Should show ~9 tables (users, students, rooms, etc.)

---

### **Step 2️⃣: Verify Configuration**

#### **Check Database Connection Settings**
Edit file: `C:\xampp\htdocs\testfinal\config\config.php`

```php
// Should match your MySQL setup:
define('DB_HOST',    '127.0.0.1');  // localhost
define('DB_PORT',    '3306');        // MySQL default port
define('DB_NAME',    'ktx');         // database name
define('DB_USER',    'root');        // MySQL username
define('DB_PASS',    '');            // MySQL password (empty if none)
```

**If you have MySQL password**, update:
```php
define('DB_PASS',    'your_password_here');
```

#### **Check APP_URL**
Ensure this line exists:
```php
define('APP_URL', 'http://localhost/testfinal/public');
```

---

### **Step 3️⃣: Seed Sample Data**

**Run database demo script to create test data:**

```powershell
# Navigate to project directory
cd C:\xampp\htdocs\testfinal

# Run PHP seeder script
php test/database_demo.php
```

**Expected Output:**
```
✅ Sample users created
✅ Sample students created
✅ Sample rooms created
✅ Sample allocations created
✅ Sample utilities created
✅ Sample violations created
✅ Sample billing records created
```

**This creates test credentials:**
- **Admin**: `admin` / `admin123`
- **Student 1**: `student01` / `student123`
- **Student 2**: `student02` / `student123`

---

### **Step 4️⃣: Start Web Server**

#### **Start XAMPP Services:**

```powershell
# Method 1: Using XAMPP Control Panel
# 1. Open: C:\xampp\xampp-control.exe
# 2. Click "Start" for Apache
# 3. Click "Start" for MySQL

# Method 2: Using PowerShell (Admin)
# For Apache:
Start-Service -Name "Apache2.4"

# For MySQL:
Start-Service -Name "MySQL80"  # or MySQL57, depending on version
```

**Verify Services Running:**
```powershell
Get-Service Apache2.4, MySQL80 | Format-Table Name, Status
```

**Expected**: Both should show `Status: Running`

---

### **Step 5️⃣: Test the Application**

#### **Access Home Page**
```
URL: http://localhost/testfinal/public/
```

**Expected**: You should see:
- Navigation menu
- Welcome message
- Login link

#### **Login as ADMIN**
```
URL: http://localhost/testfinal/public/auth/login

Username: admin
Password: admin123
```

**Admin Dashboard Features:**
- [ ] User Management
- [ ] Room Management
- [ ] Billing System
- [ ] Violation Tracking
- [ ] Reports

#### **Login as STUDENT**
```
URL: http://localhost/testfinal/public/auth/login

Username: student01
Password: student123
```

**Student Dashboard Features:**
- [ ] View personal information
- [ ] View room allocation
- [ ] Download invoices
- [ ] Check violations

---

## **Testing All Features**

### **✅ Feature Checklist - Admin Functions**

```
Admin Dashboard:
□ Login successfully
□ View dashboard statistics
□ See total users, rooms, violations
□ See recent violations

User Management:
□ Navigate to Users page
□ View list of all students
□ Click on student to view details
□ (Optional) Add new student
□ (Optional) Edit student info
□ (Optional) Ban/Unban student

Room Management:
□ Navigate to Rooms page
□ View all available/occupied rooms
□ Allocate student to room
□ View room details
□ See room occupants

Billing System:
□ Navigate to Billing
□ View billing records
□ Generate invoice for month
□ View invoice PDF
□ Update utility rates (if available)

Violation Management:
□ Navigate to Violations
□ View list of violations
□ Add new violation for student
□ Mark violation as resolved
□ View violation history
```

### **✅ Feature Checklist - Student Functions**

```
Dashboard:
□ Login successfully
□ View personal dashboard
□ See allocated room
□ See account status

Room Info:
□ View room number and type
□ See roommates (if any)
□ View room amenities (AC, etc.)

Invoices:
□ Navigate to Invoices page
□ View invoice list
□ Download invoice as PDF
□ See charges breakdown
□ View payment status

Violations:
□ View violation records (if any)
□ See violation details
□ See points deducted
```

---

## **Troubleshooting Guide**

### ❌ **Issue: "Connection refused" or "Cannot connect to database"**

**Solution:**
```powershell
# 1. Check MySQL is running
Get-Service MySQL80 | Select-Object Status

# 2. Start MySQL if stopped
Start-Service MySQL80

# 3. Test MySQL connection
mysql -u root -h 127.0.0.1 -e "SELECT 1;"

# 4. Verify database exists
mysql -u root -e "SHOW DATABASES;"

# 5. Check config.php has correct credentials
```

---

### ❌ **Issue: "404 Page Not Found" or "Route not found"**

**Solution:**
```powershell
# 1. Verify Apache mod_rewrite is enabled
# In XAMPP: Edit C:\xampp\apache\conf\httpd.conf
# Find and uncomment: LoadModule rewrite_module modules/mod_rewrite.so

# 2. Check .htaccess exists and has rewrite rules
Test-Path C:\xampp\htdocs\testfinal\public\.htaccess

# 3. Verify APP_URL in config.php
# Should be: http://localhost/testfinal/public

# 4. Clear browser cache (Ctrl+Shift+Delete)

# 5. Restart Apache
# Stop Apache, then Start Apache in XAMPP Control Panel
```

---

### ❌ **Issue: "Blank page" or "White screen of death"**

**Solution:**
```powershell
# 1. Enable debug mode in config.php
# Change: define('APP_DEBUG', true);

# 2. Check PHP error log
# Location: C:\xampp\php\php_error.log

# 3. Check Apache error log
# Location: C:\xampp\apache\logs\error.log

# 4. Test PHP syntax
php -l C:\xampp\htdocs\testfinal\public\index.php

# 5. Check file permissions
# All files should be readable by Apache
```

---

### ❌ **Issue: "Call to undefined function" or "Class not found"**

**Solution:**
```powershell
# 1. Check require_once paths in index.php
# Edit: C:\xampp\htdocs\testfinal\public\index.php

# 2. Verify all core files exist:
Test-Path C:\xampp\htdocs\testfinal\app\core\Database.php
Test-Path C:\xampp\htdocs\testfinal\app\core\BaseController.php
Test-Path C:\xampp\htdocs\testfinal\app\core\Router.php

# 3. Check class names match file names
# File: UserController.php → class UserController

# 4. Verify namespace usage (if using namespaces)
```

---

## **Project Structure Explanation**

```
testfinal/
├── app/
│   ├── core/               ← Framework foundation
│   │   ├── Database.php    ← MySQL connection & queries
│   │   ├── BaseModel.php   ← Parent class for models
│   │   ├── BaseController.php ← Parent class for controllers
│   │   ├── Router.php      ← URL routing & dispatch
│   │   └── Validator.php   ← Form validation rules
│   │
│   ├── models/             ← Data access layer
│   │   └── Models.php      ← User, Student, Room, etc.
│   │
│   ├── controllers/        ← Business logic
│   │   ├── HomeController.php
│   │   ├── UserController.php
│   │   ├── RoomControllers.php
│   │   ├── BillingController.php
│   │   ├── ViolationController.php
│   │   └── RegistrationController.php
│   │
│   ├── services/           ← Complex operations
│   │   ├── BillingService.php      ← Invoice calculations
│   │   ├── RoomAllocationService.php
│   │   ├── ViolationService.php
│   │   └── InvoicePdfGenerator.php
│   │
│   └── views/              ← HTML templates
│       ├── auth_login_page.php
│       ├── admin_dashboard_page.php
│       ├── users_list_view.php
│       ├── room_list_page.php
│       ├── violations_list_view.php
│       └── student_invoices_page.php
│
├── config/
│   └── config.php          ← Global configuration
│
├── middleware/
│   └── Middleware.php      ← Auth & permission checking
│
├── routes/
│   └── web.php             ← Route definitions (URL → Controller)
│
├── public/
│   ├── index.php           ← Entry point (front controller)
│   ├── .htaccess           ← URL rewriting rules
│   └── assets/             ← CSS, JS, Images (if any)
│
├── test/
│   └── database_demo.php   ← Sample data seeder
│
└── ktx.sql                 ← Database schema
```

---

## **Key Application Flows**

### **User Login Flow**
```
1. User visits: /auth/login
2. GET request → RegistrationController::login() → show login form
3. User enters credentials and clicks submit
4. POST request → RegistrationController::doLogin()
5. Validate credentials against database
6. If valid → create session → redirect to dashboard
7. If invalid → show error → redirect back to login
```

### **Admin Adds New Student**
```
1. Admin: /admin/users → UserController::index()
2. Admin: Click "Add Student" → UserController::create()
3. Show form
4. Admin fills form and submits → UserController::store()
5. Validate data → Insert into database
6. Redirect to users list with success message
```

### **Student Views Invoice**
```
1. Student: /invoices → BillingController::index()
2. Query database for invoices for this student
3. Display list of invoices
4. Student clicks on invoice → BillingController::show($id)
5. Generate/retrieve PDF
6. Download to computer
```

---

## **Database Schema Overview**

### **Core Tables**
| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `users` | Login accounts | id, username, email, password_hash, role, status |
| `students` | Student info | id, user_id, name, student_id, dorm_year |
| `rooms` | Dormitory rooms | id, room_number, capacity, type, has_ac |
| `room_registrations` | Room assignments | id, student_id, room_id, check_in_date, check_out_date |
| `utility_readings` | Electric/water usage | id, room_id, month, electricity, water |
| `billing_records` | Invoices | id, student_id, month, total_amount, paid_at |
| `violation_records` | Warnings/violations | id, student_id, violation_type, points, created_at |
| `maintenance_requests` | Repair tickets | id, room_id, description, status |

---

## **API Endpoints (if applicable)**

```
GET  /api/rooms              ← List all rooms
GET  /api/rooms/:id          ← Get room details
POST /api/rooms              ← Create room (admin only)

GET  /api/users              ← List users (admin only)
GET  /api/users/:id          ← Get user details
POST /api/users              ← Create user (admin only)

GET  /api/invoices/:id       ← Get invoice PDF
GET  /api/violations         ← List violations (admin)
```

---

## **Next Steps After Setup**

1. ✅ Customize branding (colors, logo, footer)
2. ✅ Add email notifications
3. ✅ Set up daily/monthly automatic billing
4. ✅ Add import/export features
5. ✅ Set up backups
6. ✅ Configure production deployment

---

## **Common Commands Cheat Sheet**

```powershell
# View MySQL error log
Get-Content C:\xampp\mysql\data\*.err

# View Apache error log
Get-Content C:\xampp\apache\logs\error.log

# Restart Apache
Stop-Service Apache2.4; Start-Service Apache2.4

# Restart MySQL
Stop-Service MySQL80; Start-Service MySQL80

# Check PHP syntax in all files
Get-ChildItem -Path "C:\xampp\htdocs\testfinal" -Filter "*.php" -Recurse | ForEach-Object { php -l $_.FullName }

# Export database backup
mysqldump -u root ktx > backup_ktx_$(Get-Date -Format "yyyyMMdd").sql
```

---

**✨ System is now ready for use!**
**Created**: 2026-05-30
**Last Updated**: 2026-05-30
