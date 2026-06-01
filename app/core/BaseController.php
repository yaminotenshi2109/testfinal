<?php
/**
 * core/BaseController.php
 * ─────────────────────────────────────────────────────────────
 *  Lớp Controller gốc — mọi Controller kế thừa từ đây
 *
 *  Cung cấp:
 *    • view()         — render PHP view với layout
 *    • json()         — trả về JSON (REST API / AJAX)
 *    • redirect()     — chuyển hướng HTTP
 *    • flash()        — thông báo 1 lần qua session
 *    • request()      — đọc input an toàn (sanitized)
 *    • validate()     — validation phía server
 *    • auth()         — thông tin user đăng nhập
 *    • abort()        — dừng và trả HTTP error code
 *    • paginate()     — helper phân trang
 *    • isAjax()       — kiểm tra Ajax request
 *    • old()          — giữ lại giá trị form cũ sau lỗi
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

abstract class BaseController
{
    // ── View base path ────────────────────────────────────────
    protected string $viewPath   = __DIR__ . '/../views';
    protected string $layoutPath = __DIR__ . '/../views/layouts/main.php';

    // ── Default layout (false = không dùng layout) ────────────
    protected string|false $layout = 'main';

    // ── DB shortcut ───────────────────────────────────────────
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->startSession();
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }


    // =========================================================
    //  VIEW RENDERING
    // =========================================================

    /**
     * Render một view PHP với layout.
     *
     * @param string $view   Đường dẫn tương đối: 'rooms/index', 'admin/dashboard'
     * @param array  $data   Biến truyền vào view — được extract()
     * @param string|false $layout  Override layout mặc định ('false' để bỏ layout)
     *
     * Ví dụ:
     *   $this->view('rooms/index', ['rooms' => $rooms, 'title' => 'Danh sách phòng']);
     *   $this->view('auth/login', [], false);  // không layout
     */
    protected function view(
        string       $view,
        array        $data   = [],
        string|false $layout = null
    ): void {
        $viewFile = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View không tồn tại: {$viewFile}");
        }

        // Inject biến chung vào mọi view
        $data['_flash']     = $this->getFlash();
        $data['_auth']      = $this->auth();
        $data['_old']       = $_SESSION['_old_input'] ?? [];
        
        // Flatten errors array-of-arrays to array-of-strings
        $rawErrors = $_SESSION['_errors'] ?? [];
        $flatErrors = [];
        foreach ($rawErrors as $field => $messages) {
            if (is_array($messages)) {
                $flatErrors[$field] = reset($messages) ?: '';
            } else {
                $flatErrors[$field] = (string)$messages;
            }
        }
        $data['_errors']    = $flatErrors;
        $data['_csrfToken'] = $this->csrfToken();

        // Xóa old input và errors sau khi đã lấy
        unset($_SESSION['_old_input'], $_SESSION['_errors']);

        // Extract data thành biến cục bộ (accessible trong view)
        extract($data, EXTR_SKIP);

        // Dùng layout nào?
        $useLayout = $layout ?? $this->layout;

        if ($useLayout === false || $useLayout === 'none') {
            // Render thẳng, không layout
            require $viewFile;
            return;
        }

        $layoutFile = $this->viewPath . '/layouts/' . $useLayout . '.php';
        if (!file_exists($layoutFile)) {
            // Fallback: render không layout
            require $viewFile;
            return;
        }

        // Capture view content → biến $content cho layout
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render layout (layout dùng echo $content)
        require $layoutFile;
    }


    // =========================================================
    //  JSON RESPONSE
    // =========================================================

    /**
     * Trả về JSON response — dùng cho AJAX / REST API.
     *
     * @param mixed $data     Dữ liệu trả về
     * @param int   $status   HTTP status code
     * @param array $headers  Header bổ sung
     *
     * Ví dụ:
     *   $this->json(['rooms' => $rooms]);
     *   $this->json(['message' => 'Không tìm thấy'], 404);
     *   $this->json(['errors' => $errors], 422);
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Chuẩn hóa JSON response thành format nhất quán:
     * { success, data, message, meta? }
     *
     * Ví dụ:
     *   $this->jsonOk($rooms, 'Lấy danh sách phòng thành công');
     *   $this->jsonOk($room, 'Tạo phòng thành công', 201, ['total' => 5]);
     */
    protected function jsonOk(
        mixed  $data    = null,
        string $message = 'Thành công',
        int    $status  = 200,
        array  $meta    = []
    ): never {
        $response = ['success' => true, 'message' => $message, 'data' => $data];
        if (!empty($meta)) {
            $response['meta'] = $meta;
        }
        $this->json($response, $status);
    }

    /**
     * JSON error response.
     *
     * Ví dụ:
     *   $this->jsonError('Không tìm thấy phòng', 404);
     *   $this->jsonError('Dữ liệu không hợp lệ', 422, $errors);
     */
    protected function jsonError(
        string $message,
        int    $status = 400,
        array  $errors = []
    ): never {
        $response = ['success' => false, 'message' => $message];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        $this->json($response, $status);
    }


    // =========================================================
    //  REDIRECT
    // =========================================================

    /**
     * Redirect đến URL khác.
     *
     * Ví dụ:
     *   $this->redirect('/rooms');
     *   $this->redirect('/login')->with('error', 'Vui lòng đăng nhập');
     */
    protected function redirect(string $url, int $status = 302): never
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
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    /** Redirect về trang trước (Referer) */
    protected function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }


    // =========================================================
    //  FLASH MESSAGES
    // =========================================================

    /**
     * Đặt flash message (hiển thị 1 lần ở request tiếp theo).
     *
     * @param string $type  'success' | 'error' | 'warning' | 'info'
     *
     * Ví dụ:
     *   $this->flash('success', 'Tạo phòng thành công!');
     *   $this->redirect('/rooms');
     */
    protected function flash(string $type, string $message): void
    {
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    private function getFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }


    // =========================================================
    //  REQUEST INPUT
    // =========================================================

    /**
     * Đọc input an toàn từ POST / GET / JSON body.
     *
     * @param string|null $key      null = lấy tất cả
     * @param mixed       $default  Giá trị mặc định nếu key không tồn tại
     *
     * Ví dụ:
     *   $name   = $this->request('full_name');
     *   $all    = $this->request();
     *   $status = $this->request('status', 'pending');
     */
    protected function request(?string $key = null, mixed $default = null): mixed
    {
        // Ưu tiên JSON body (từ fetch() API)
        $data = $this->parseJsonBody() ?? [];

        // Merge POST rồi GET (POST override JSON body nếu cả hai có)
        $data = array_merge($data, $_GET, $_POST);

        if ($key === null) {
            return $this->sanitizeArray($data);
        }

        return isset($data[$key])
            ? $this->sanitize($data[$key])
            : $default;
    }

    /** Lấy nhiều key cùng lúc */
    protected function only(array $keys): array
    {
        $all    = $this->request();
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $all[$key] ?? null;
        }
        return $result;
    }

    private function parseJsonBody(): ?array
    {
        $ct = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!str_contains($ct, 'application/json')) {
            return null;
        }
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
        }
        if (is_array($value)) {
            return $this->sanitizeArray($value);
        }
        return $value;
    }

    private function sanitizeArray(array $data): array
    {
        return array_map(fn($v) => $this->sanitize($v), $data);
    }


    // =========================================================
    //  SERVER-SIDE VALIDATION
    // =========================================================

    /**
     * Validate dữ liệu theo rules.
     *
     * Rules hỗ trợ:
     *   required | min:N | max:N | email | numeric | in:a,b,c
     *   regex:/pattern/ | unique:table,column[,excludeId]
     *   confirmed (kiểm tra field_confirm)
     *
     * Ví dụ:
     *   $errors = $this->validate($this->request(), [
     *       'full_name'    => 'required|min:2|max:100',
     *       'email'        => 'required|email|unique:users,email',
     *       'password'     => 'required|min:8|confirmed',
     *       'role'         => 'required|in:admin,student',
     *       'priority_level' => 'required|numeric|min:0|max:2',
     *   ]);
     *
     *   if (!empty($errors)) {
     *       $this->jsonError('Dữ liệu không hợp lệ', 422, $errors);
     *   }
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value    = $data[$field] ?? null;
            $ruleParts = explode('|', $ruleString);

            foreach ($ruleParts as $rule) {
                [$ruleName, $ruleArg] = array_pad(explode(':', $rule, 2), 2, null);

                $error = match ($ruleName) {
                    'required' => $this->vRequired($value),
                    'min'      => $this->vMin($value, (int)$ruleArg),
                    'max'      => $this->vMax($value, (int)$ruleArg),
                    'email'    => $this->vEmail($value),
                    'numeric'  => $this->vNumeric($value),
                    'in'       => $this->vIn($value, explode(',', $ruleArg)),
                    'regex'    => $this->vRegex($value, $ruleArg),
                    'unique'   => $this->vUnique($field, $value, $ruleArg),
                    'confirmed'=> $this->vConfirmed($value, $data["{$field}_confirm"] ?? null),
                    'date'     => $this->vDate($value),
                    'url'      => $this->vUrl($value),
                    'integer'  => $this->vInteger($value),
                    default    => null,
                };

                if ($error !== null) {
                    $errors[$field][] = $error;
                    break; // dừng ở lỗi đầu tiên của field này
                }
            }
        }

        return $errors;
    }

    // Validation rule methods
    private function vRequired(mixed $v): ?string
    {
        return ($v === null || $v === '' || (is_array($v) && empty($v)))
            ? 'Trường này là bắt buộc.' : null;
    }
    private function vMin(mixed $v, int $n): ?string
    {
        if ($v === null || $v === '') return null;
        return (is_numeric($v) ? (float)$v < $n : mb_strlen((string)$v) < $n)
            ? (is_numeric($v) ? "Giá trị tối thiểu là {$n}." : "Tối thiểu {$n} ký tự.") : null;
    }
    private function vMax(mixed $v, int $n): ?string
    {
        if ($v === null || $v === '') return null;
        return (is_numeric($v) ? (float)$v > $n : mb_strlen((string)$v) > $n)
            ? (is_numeric($v) ? "Giá trị tối đa là {$n}." : "Tối đa {$n} ký tự.") : null;
    }
    private function vEmail(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;
        return filter_var($v, FILTER_VALIDATE_EMAIL) === false
            ? 'Email không hợp lệ.' : null;
    }
    private function vNumeric(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;
        return !is_numeric($v) ? 'Phải là số.' : null;
    }
    private function vInteger(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;
        return filter_var($v, FILTER_VALIDATE_INT) === false ? 'Phải là số nguyên.' : null;
    }
    private function vIn(mixed $v, array $allowed): ?string
    {
        if ($v === null || $v === '') return null;
        return !in_array($v, $allowed, true)
            ? 'Giá trị không hợp lệ. Cho phép: ' . implode(', ', $allowed) . '.' : null;
    }
    private function vRegex(mixed $v, string $pattern): ?string
    {
        if ($v === null || $v === '') return null;
        return !preg_match($pattern, (string)$v) ? 'Định dạng không hợp lệ.' : null;
    }
    private function vUnique(string $field, mixed $v, string $arg): ?string
    {
        if ($v === null || $v === '') return null;
        [$table, $column, $excludeId] = array_pad(explode(',', $arg), 3, null);
        $where  = "{$column} = ?";
        $params = [$v];
        if ($excludeId) {
            $where   .= ' AND id != ?';
            $params[] = (int)$excludeId;
        }
        return $this->db->exists($table, $where, $params)
            ? "Giá trị này đã tồn tại trong hệ thống." : null;
    }
    private function vConfirmed(mixed $v, mixed $confirm): ?string
    {
        return $v !== $confirm ? 'Xác nhận không khớp.' : null;
    }
    private function vDate(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;
        $d = \DateTime::createFromFormat('Y-m-d', (string)$v);
        return (!$d || $d->format('Y-m-d') !== $v) ? 'Ngày không hợp lệ (YYYY-MM-DD).' : null;
    }
    private function vUrl(mixed $v): ?string
    {
        if ($v === null || $v === '') return null;
        return filter_var($v, FILTER_VALIDATE_URL) === false ? 'URL không hợp lệ.' : null;
    }


    // =========================================================
    //  AUTH HELPERS
    // =========================================================

    /**
     * Lấy thông tin user đang đăng nhập.
     *
     * Ví dụ:
     *   $user = $this->auth();          // ['id'=>1,'role'=>'admin',...]
     *   $id   = $this->auth('id');
     *   $role = $this->auth('role');
     */
    protected function auth(?string $key = null): mixed
    {
        $user = $_SESSION['_auth_user'] ?? null;
        if ($key === null) return $user;
        return $user[$key] ?? null;
    }

    /** Kiểm tra đã đăng nhập chưa */
    protected function isLoggedIn(): bool
    {
        return isset($_SESSION['_auth_user']);
    }

    /** Kiểm tra role */
    protected function isAdmin(): bool
    {
        return $this->auth('role') === 'admin';
    }

    protected function isStudent(): bool
    {
        return $this->auth('role') === 'student';
    }

    /**
     * Đăng nhập — lưu user vào session.
     * Gọi sau khi xác thực thành công.
     */
    protected function loginUser(array $user): void
    {
        // Regenerate session ID để chống Session Fixation
        session_regenerate_id(true);
        $_SESSION['_auth_user'] = $user;
    }

    /** Đăng xuất */
    protected function logoutUser(): void
    {
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    /**
     * Yêu cầu đăng nhập — dừng nếu chưa auth.
     *
     * Ví dụ (dùng trong constructor controller):
     *   $this->requireAuth();
     *   $this->requireAuth('/login');
     */
    protected function requireAuth(string $redirectTo = '/login'): void
    {
        if (!$this->isLoggedIn()) {
            if ($this->isAjax()) {
                $this->jsonError('Chưa đăng nhập.', 401);
            }
            $this->flash('error', 'Vui lòng đăng nhập để tiếp tục.');
            $this->redirect($redirectTo);
        }
    }

    /** Yêu cầu quyền admin */
    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            if ($this->isAjax()) {
                $this->jsonError('Không có quyền truy cập.', 403);
            }
            $this->abort(403);
        }
    }


    // =========================================================
    //  ABORT / HTTP ERROR
    // =========================================================

    /**
     * Dừng request với HTTP error code.
     *
     * Ví dụ:
     *   $room = $roomModel->find($id);
     *   if (!$room) $this->abort(404, 'Phòng không tồn tại');
     */
    protected function abort(int $code, string $message = ''): never
    {
        http_response_code($code);

        if ($this->isAjax()) {
            $defaultMessages = [
                400 => 'Yêu cầu không hợp lệ.',
                401 => 'Chưa xác thực.',
                403 => 'Không có quyền truy cập.',
                404 => 'Không tìm thấy.',
                500 => 'Lỗi máy chủ.',
            ];
            $this->jsonError($message ?: ($defaultMessages[$code] ?? 'Lỗi'), $code);
        }

        $errorView = $this->viewPath . "/errors/{$code}.php";
        if (file_exists($errorView)) {
            $errorMessage = $message;
            require $errorView;
        } else {
            echo "<h1>HTTP {$code}</h1>";
            if ($message) echo "<p>{$message}</p>";
        }
        exit;
    }


    // =========================================================
    //  CSRF PROTECTION
    // =========================================================

    protected function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Kiểm tra CSRF token — gọi trong POST handler.
     *
     * Ví dụ:
     *   public function store(): void {
     *       $this->verifyCsrf();
     *       // ... xử lý form
     *   }
     */
    protected function verifyCsrf(): void
    {
        $token = $this->request('_csrf_token')
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        if (!hash_equals($this->csrfToken(), $token)) {
            $this->abort(419, 'CSRF token không hợp lệ. Vui lòng tải lại trang.');
        }
    }


    // =========================================================
    //  UTILITY HELPERS
    // =========================================================

    /** Kiểm tra có phải Ajax / JSON request không */
    protected function isAjax(): bool
    {
        $xhr    = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $ct     = $_SERVER['CONTENT_TYPE'] ?? '';
        return $xhr === 'xmlhttprequest'
            || str_contains($accept, 'application/json')
            || str_contains($ct, 'application/json');
    }

    /** Giữ lại giá trị form cũ sau validation fail */
    protected function withOldInput(array $data): void
    {
        $_SESSION['_old_input'] = $data;
    }

    /** Lưu validation errors vào session rồi redirect */
    protected function withErrors(array $errors, string $redirectTo = ''): never
    {
        $_SESSION['_errors'] = $errors;
        if ($redirectTo) {
            $this->redirect($redirectTo);
        }
        $this->back();
    }

    /** Phân trang params từ URL */
    protected function paginationParams(): array
    {
        $page    = max(1, (int)($this->request('page') ?? 1));
        $perPage = min(100, max(5, (int)($this->request('per_page') ?? 15)));
        return [$page, $perPage];
    }

    /** Set HTTP headers bảo mật */
    protected function secureHeaders(): void
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /** Format tiền Việt Nam */
    protected function formatVnd(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' VND';
    }

    /** Lấy route param (truyền từ Router) */
    protected function param(array $params, string $key, mixed $default = null): mixed
    {
        return $params[$key] ?? $default;
    }
}
