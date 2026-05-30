# KTX (Dormitory Management System) - Setup & Implementation Guide

## 📋 Project Overview
- **Name**: Hệ Thống Quản Lý Ký Túc Xá (KTX Management System)
- **Type**: Web Application (PHP MVC)
- **Database**: MySQL/MariaDB
- **Server**: Apache (via XAMPP)
- **Language**: PHP 7.4+

---

## ✅ Step 1: Environment Verification

### Prerequisites
- XAMPP installed and running
- MySQL/MariaDB service active
- Apache web server active
- PHP 7.4 or higher

### Check Installation
```powershell
# Check XAMPP status
Get-Service | Select-Object Name, Status | Where-Object {$_.Name -like '*XAMPP*' -or $_.Name -like '*Apache*' -or $_.Name -like '*MySQL*'}

# Check PHP version
php -v

# Check MySQL version
mysql --version
```

---

## 🗄️ Step 2: Database Setup

### Create Database and Import Schema
```sql
-- Login to MySQL
mysql -u root -p

-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS ktx
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Import schema from ktx.sql
USE ktx;
SOURCE C:\xampp\htdocs\testfinal\ktx.sql;

-- Verify tables created
SHOW TABLES;
```

### Quick Import via Command Line
```powershell
# From project directory
mysql -u root -p ktx < C:\xampp\htdocs\testfinal\ktx.sql
```

---

## ⚙️ Step 3: Configuration

### Update Database Credentials
Edit `config/config.php`:

```php
define('DB_HOST',    '127.0.0.1');    // localhost
define('DB_PORT',    '3306');          // MySQL default port
define('DB_NAME',    'ktx');           // database name
define('DB_USER',    'root');          // MySQL username
define('DB_PASS',    '');              // MySQL password (empty if no password)
```

### Verify APP_URL
```php
define('APP_URL', 'http://localhost/testfinal/public');
```

---

## 🌱 Step 4: Seed Sample Data

Run the database demo script to populate sample data:

```powershell
cd C:\xampp\htdocs\testfinal
php test/database_demo.php
```

This will create:
- Sample admin user (username: `admin`, password: `admin123`)
- Sample student accounts
- Room data
- Utility readings
- Violations
- Invoices

---

## 🚀 Step 5: Web Server Setup

### Verify Apache Routing

Ensure `.htaccess` exists in `public/` folder with proper rewrite rules:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /testfinal/public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L,QSA]
</IfModule>
```

### Enable mod_rewrite
```powershell
# In XAMPP Apache config (httpd.conf)
# Ensure this line is uncommented:
# LoadModule rewrite_module modules/mod_rewrite.so

# Restart Apache
# XAMPP Control Panel → Apache → Stop, then Start
```

---

## 🧪 Step 6: Test Application

### Access the Application
1. **Home Page**
   - URL: `http://localhost/testfinal/public/`
   - Expected: Home page with navigation menu

2. **Login (Student)**
   - URL: `http://localhost/testfinal/public/auth/login`
   - Test credentials:
     - Username: `student01`
     - Password: `student123`

3. **Login (Admin)**
   - Test credentials:
     - Username: `admin`
     - Password: `admin123`

### Test Core Features

#### As Admin:
- [ ] **Dashboard**: View system overview
- [ ] **User Management**: List/Create/Edit/Delete students
- [ ] **Room Management**: View rooms, allocate rooms
- [ ] **Billing**: Generate invoices, view billing history
- [ ] **Violations**: Manage student violations/warnings

#### As Student:
- [ ] **Dashboard**: View personal info
- [ ] **Room Info**: View allocated room
- [ ] **Invoice**: Download/view invoices
- [ ] **Violations**: View violation records

---

## 📂 Project Structure

```
testfinal/
├── app/
│   ├── core/              # Framework core
│   │   ├── Database.php   # Database connection & query
│   │   ├── BaseModel.php  # Parent model class
│   │   ├── BaseController.php # Parent controller class
│   │   ├── Router.php     # URL routing
│   │   └── Validator.php  # Form validation
│   ├── models/            # Data models
│   │   └── Models.php     # Model classes
│   ├── controllers/       # Business logic
│   │   ├── HomeController.php
│   │   ├── UserController.php
│   │   ├── RoomControllers.php
│   │   ├── BillingController.php
│   │   ├── ViolationController.php
│   │   └── RegistrationController.php
│   ├── services/          # Business services
│   │   ├── BillingService.php
│   │   ├── RoomAllocationService.php
│   │   ├── ViolationService.php
│   │   └── InvoicePdfGenerator.php
│   └── views/             # HTML templates
│       ├── admin_dashboard_page.php
│       ├── auth_login_page.php
│       ├── users_list_view.php
│       ├── room_list_page.php
│       ├── violations_list_view.php
│       └── student_invoices_page.php
├── config/
│   └── config.php         # Configuration constants
├── middleware/
│   └── Middleware.php     # Authentication/Authorization
├── routes/
│   └── web.php            # Route definitions
├── public/
│   ├── index.php          # Front controller
│   └── .htaccess          # URL rewriting
├── test/
│   └── database_demo.php  # Sample data seeder
├── ktx.sql                # Database schema
└── README.md              # Project documentation
```

---

## 🔧 Troubleshooting

### Issue: Database Connection Failed
**Solution**: 
- Check MySQL is running
- Verify credentials in `config/config.php`
- Ensure database `ktx` exists

### Issue: 404 Page Not Found
**Solution**:
- Enable `mod_rewrite` in Apache
- Check `.htaccess` in `public/` folder
- Verify `APP_URL` in `config/config.php`

### Issue: Blank Page / No Errors
**Solution**:
- Enable `APP_DEBUG = true` in `config/config.php`
- Check PHP error logs in XAMPP
- Look for `error.log` in project root

### Issue: Permission Denied on Upload
**Solution**:
- Ensure `storage/` folder has write permissions
- Run `chmod 755 storage/` on Linux/Mac

---

## 📊 Database Tables

| Table | Purpose |
|-------|---------|
| `users` | User accounts (admin, student) |
| `students` | Student information |
| `rooms` | Room/dormitory data |
| `room_registrations` | Student room assignments |
| `utility_readings` | Electricity/water readings |
| `billing_records` | Invoice/payment records |
| `violation_records` | Student violations/warnings |
| `maintenance_requests` | Room maintenance requests |
| `notifications` | System notifications |

---

## 🎯 Key Features Implemented

✅ User Authentication & Authorization
✅ Student & Room Management
✅ Utility Billing System
✅ Violation Management
✅ Invoice Generation
✅ Room Allocation
✅ Admin Dashboard
✅ Student Dashboard
✅ Form Validation
✅ Error Handling

---

## 📝 Notes

- All database operations use prepared statements (prevent SQL injection)
- Passwords are hashed using PHP's `password_hash()` function
- Timestamps use UTC+7 (Vietnam timezone)
- Database follows 3NF normalization
- All views use UTF-8 encoding

---

## ✨ Next Steps

After successful setup:
1. Add more sample data as needed
2. Customize UI/Branding
3. Set up email notifications
4. Configure backup schedule
5. Deploy to production

---

**Last Updated**: 2026-05-30
**Created by**: KTX Management System Team
