<?php
/**
 * public/index.php
 * ─────────────────────────────────────────────────────────────
 *  Front Controller — điểm vào duy nhất của ứng dụng
 *
 *  Apache .htaccess redirect tất cả request về đây:
 *    RewriteRule ^(.*)$ index.php [L,QSA]
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

if (!function_exists('getDynamicUrl')) {
    function getDynamicUrl(string $path): string {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $publicBase = rtrim(dirname($scriptName), '/\\');
        $rootBase   = rtrim(dirname($publicBase), '/\\');

        if (str_contains($requestUri, '/public/') || str_ends_with($requestUri, '/public')) {
            $base = $publicBase;
        } else {
            $base = ($rootBase !== '' && $rootBase !== '/' && $rootBase !== '\\') ? $rootBase : '';
        }

        return $base . $path;
    }
}

// ── Bảo mật: không hiện lỗi PHP ra browser ───────────────────
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ── Đường dẫn gốc ────────────────────────────────────────────
define('BASE_PATH', dirname(__DIR__));

// ── Autoload core ─────────────────────────────────────────────
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/app/core/Database.php';
require_once BASE_PATH . '/app/core/BaseModel.php';
require_once BASE_PATH . '/app/core/BaseController.php';
require_once BASE_PATH . '/app/core/Router.php';
require_once BASE_PATH . '/middleware/Middleware.php';

// ── Security headers mặc định ────────────────────────────────
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// ── Đăng ký routes ───────────────────────────────────────────
require_once BASE_PATH . '/routes/web.php';

// ── Dispatch ─────────────────────────────────────────────────
try {
    Router::getInstance()->dispatch();
} catch (DatabaseException $e) {
    http_response_code(500);
    error_log('[FATAL] ' . $e->getMessage());

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ. Vui lòng thử lại.']);
    } else {
        require BASE_PATH . '/app/views/errors/500.php';
    }
} catch (\Throwable $e) {
    http_response_code(500);
    error_log('[FATAL] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<pre><strong>{$e->getMessage()}</strong>\n{$e->getTraceAsString()}</pre>";
    } else {
        require BASE_PATH . '/app/views/errors/500.php';
    }
}
