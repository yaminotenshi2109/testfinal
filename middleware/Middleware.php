<?php
/**
 * middleware/AuthMiddleware.php
 * ─────────────────────────────────────────────────────────────
 *  Kiểm tra đã đăng nhập chưa.
 *  Dùng cho tất cả route cần auth (admin + student)
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

if (!function_exists('middlewareRedirect')) {
    function middlewareRedirect(string $url): never
    {
        if (str_starts_with($url, '/')) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $publicBase = rtrim(dirname($scriptName), '/\\');
            $rootBase   = rtrim(dirname($publicBase), '/\\');

            if (str_contains($requestUri, '/public/') || str_ends_with($requestUri, '/public')) {
                $base = $publicBase;
            } else {
                $base = ($rootBase !== '' && $rootBase !== '/' && $rootBase !== '\\') ? $rootBase : '';
            }

            if ($base !== '' && !str_starts_with($url, $base)) {
                $url = $base . $url;
            }
        }
        header("Location: {$url}");
        exit;
    }
}

class AuthMiddleware
{
    public function handle(callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['_auth_user'])) {
            $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập.', 'code' => 401]);
                exit;
            }

            $_SESSION['_flash'][]       = ['type' => 'warning', 'message' => 'Vui lòng đăng nhập.'];
            $_SESSION['_intended_url']  = $_SERVER['REQUEST_URI'] ?? '/';
            middlewareRedirect('/auth/login');
        }

        $next();
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * middleware/AdminMiddleware.php
 * Chạy SAU AuthMiddleware — kiểm tra role = 'admin'
 */
class AdminMiddleware
{
    public function handle(callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['_auth_user'] ?? null;

        if (!$user || ($user['role'] ?? '') !== 'admin') {
            $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
                   || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập.', 'code' => 403]);
                exit;
            }

            http_response_code(403);
            $view = __DIR__ . '/../views/errors/403.php';
            if (file_exists($view)) {
                require $view;
            } else {
                die('<h1>403 — Không có quyền truy cập</h1>');
            }
            exit;
        }

        $next();
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * middleware/GuestMiddleware.php
 * Chặn user đã đăng nhập truy cập trang login/register
 */
class GuestMiddleware
{
    public function handle(callable $next): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['_auth_user'])) {
            $role = $_SESSION['_auth_user']['role'] ?? 'student';
            $redirect = $role === 'admin' ? '/admin/dashboard' : '/student/dashboard';
            middlewareRedirect($redirect);
        }

        $next();
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * middleware/ApiMiddleware.php
 * Dùng cho route /api/* — set header JSON, bỏ CSRF check
 */
class ApiMiddleware
{
    public function handle(callable $next): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');

        // Preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        // API auth qua session (hoặc thêm Bearer token sau)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['_auth_user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated.', 'code' => 401]);
            exit;
        }

        $next();
    }
}
