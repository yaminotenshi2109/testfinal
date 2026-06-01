<?php
/**
 * config/config.php
 * ─────────────────────────────────────────────────────────────
 *  Cấu hình toàn cục hệ thống KTX
 *  Không commit file này lên Git — thêm vào .gitignore
 * ─────────────────────────────────────────────────────────────
 */

// ── Môi trường ───────────────────────────────────────────────
define('APP_ENV',   getenv('APP_ENV') ?: 'development');  // 'development' | 'production'
define('APP_NAME',  'Hệ Thống Quản Lý KTX');
define('APP_URL',   'http://localhost/testfinal/public');  // URL gốc của ứng dụng
define('APP_DEBUG', APP_ENV === 'development');

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    getenv('DB_HOST')    ?: '127.0.0.1');
define('DB_PORT',    getenv('DB_PORT')    ?: '3306');
define('DB_NAME',    getenv('DB_NAME')    ?: 'ktx');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS')    ?: '');
define('DB_CHARSET', 'utf8mb4');

// ── Violation threshold ───────────────────────────────────────
define('VIOLATION_THRESHOLD', 10);  // điểm trừ tối đa trước khi under_review

// ── Billing rates (fallback nếu không có trong utility_readings) ─
define('DEFAULT_ELEC_RATE',  3500.00);   // VND / kWh
define('DEFAULT_WATER_RATE', 15000.00);  // VND / m³
define('AC_FEE_MONTHLY',     100000.00); // VND / tháng nếu phòng có AC

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Asia/Ho_Chi_Minh');
