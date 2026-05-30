<?php
/**
 * core/Router.php
 * ─────────────────────────────────────────────────────────────
 *  URL Dispatcher cho hệ thống KTX
 *
 *  Tính năng:
 *    • Đăng ký route theo method: get(), post(), put(), delete()
 *    • Named parameters  :id  :slug  ─ capture vào $params[]
 *    • Regex constraints  ->where('id', '\d+')
 *    • Route groups       group('/admin', $fn)  — prefix + middleware stack
 *    • Named routes       ->name('room.show')   — url('room.show', ['id'=>3])
 *    • Middleware per route hoặc per group
 *    • JSON-friendly: tự detect Ajax / API và trả 404 JSON nếu cần
 *    • dispatch() là entry point duy nhất — gọi từ public/index.php
 *
 *  Quy ước:
 *    'GET /rooms'               → RoomController@index
 *    'GET /rooms/:id'           → RoomController@show     ($params['id'])
 *    'POST /rooms'              → RoomController@store
 *    'PUT /rooms/:id'           → RoomController@update
 *    'DELETE /rooms/:id'        → RoomController@destroy
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

class Router
{
    // ── Route registry ────────────────────────────────────────
    /** @var array<array{method,pattern,handler,middleware,name,constraints}> */
    private array $routes = [];

    // ── Named route map  name → url pattern ──────────────────
    private array $namedRoutes = [];

    // ── Group state (stack cho nested groups) ────────────────
    private array $groupStack = [];

    // ── Last registered route (để chain ->name(), ->where()) ─
    private int $lastIndex = -1;

    // ── Singleton-ish (sử dụng qua static facade bên dưới) ───
    private static ?Router $instance = null;

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    private function __construct() {}


    // =========================================================
    //  ROUTE REGISTRATION
    // =========================================================

    public function get(string $uri, string|callable $handler): static
    {
        return $this->addRoute('GET', $uri, $handler);
    }

    public function post(string $uri, string|callable $handler): static
    {
        return $this->addRoute('POST', $uri, $handler);
    }

    public function put(string $uri, string|callable $handler): static
    {
        return $this->addRoute('PUT', $uri, $handler);
    }

    public function patch(string $uri, string|callable $handler): static
    {
        return $this->addRoute('PATCH', $uri, $handler);
    }

    public function delete(string $uri, string|callable $handler): static
    {
        return $this->addRoute('DELETE', $uri, $handler);
    }

    /**
     * Đăng ký cùng lúc GET + POST cho một URI
     * Hữu ích cho form: GET hiện form, POST xử lý submit
     */
    public function form(string $uri, string $handler): static
    {
        $this->addRoute('GET',  $uri, $handler);
        $this->addRoute('POST', $uri, $handler);
        return $this;
    }

    /**
     * Resource routes — đăng ký đầy đủ CRUD theo chuẩn RESTful
     *
     *   GET    /rooms           → index
     *   GET    /rooms/create    → create
     *   POST   /rooms           → store
     *   GET    /rooms/:id       → show
     *   GET    /rooms/:id/edit  → edit
     *   PUT    /rooms/:id       → update
     *   DELETE /rooms/:id       → destroy
     */
    public function resource(string $uri, string $controller, array $only = []): static
    {
        $map = [
            ['GET',    $uri,              "{$controller}@index"],
            ['GET',    $uri . '/create',  "{$controller}@create"],
            ['POST',   $uri,              "{$controller}@store"],
            ['GET',    $uri . '/:id',     "{$controller}@show"],
            ['GET',    $uri . '/:id/edit',"{$controller}@edit"],
            ['PUT',    $uri . '/:id',     "{$controller}@update"],
            ['DELETE', $uri . '/:id',     "{$controller}@destroy"],
        ];

        foreach ($map as [$method, $path, $handler]) {
            $action = explode('@', $handler)[1];
            if (empty($only) || in_array($action, $only, true)) {
                $this->addRoute($method, $path, $handler);
            }
        }
        return $this;
    }


    // =========================================================
    //  ROUTE MODIFIERS (chained sau get/post/...)
    // =========================================================

    /** Đặt tên cho route vừa đăng ký */
    public function name(string $name): static
    {
        if ($this->lastIndex >= 0) {
            $this->routes[$this->lastIndex]['name'] = $name;
            $this->namedRoutes[$name] = $this->routes[$this->lastIndex]['original_uri'];
        }
        return $this;
    }

    /** Thêm middleware vào route vừa đăng ký */
    public function middleware(string|array $middleware): static
    {
        if ($this->lastIndex >= 0) {
            $existing = $this->routes[$this->lastIndex]['middleware'];
            $add      = (array)$middleware;
            $this->routes[$this->lastIndex]['middleware'] = array_unique([...$existing, ...$add]);
        }
        return $this;
    }

    /** Thêm regex constraint cho named parameter */
    public function where(string $param, string $regex): static
    {
        if ($this->lastIndex >= 0) {
            $this->routes[$this->lastIndex]['constraints'][$param] = $regex;
        }
        return $this;
    }


    // =========================================================
    //  GROUP
    // =========================================================

    /**
     * Nhóm routes với prefix và/hoặc middleware dùng chung
     *
     *   $router->group('/admin', function($r) {
     *       $r->get('/dashboard', 'AdminController@dashboard');
     *       $r->resource('/rooms', 'RoomController');
     *   }, ['auth', 'admin']);
     */
    public function group(string $prefix, callable $callback, array $middleware = []): static
    {
        // Tính prefix đầy đủ (nested group)
        $parentPrefix     = $this->currentGroupPrefix();
        $parentMiddleware = $this->currentGroupMiddleware();

        $this->groupStack[] = [
            'prefix'     => $parentPrefix . $prefix,
            'middleware' => array_unique([...$parentMiddleware, ...$middleware]),
        ];

        $callback($this);

        array_pop($this->groupStack);
        return $this;
    }


    // =========================================================
    //  DISPATCH
    // =========================================================

    /**
     * Entry point — gọi từ public/index.php
     *
     *   Router::getInstance()->dispatch();
     */
    public function dispatch(): void
    {
        $method = $this->resolveMethod();
        $uri    = $this->resolveUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = [];
            if (!$this->matchRoute($route, $uri, $params)) {
                continue;
            }

            // Chạy middleware stack
            $this->runMiddleware($route['middleware'], function () use ($route, $params) {
                $this->callHandler($route['handler'], $params);
            });

            return; // route đã được xử lý
        }

        // Không khớp route nào
        $this->handleNotFound($uri);
    }


    // =========================================================
    //  URL GENERATOR
    // =========================================================

    /**
     * Tạo URL từ tên route
     *
     *   url('room.show', ['id' => 3])   →  /rooms/3
     *   url('home')                     →  /
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route '{$name}' không tồn tại.");
        }

        $uri = $this->namedRoutes[$name];

        foreach ($params as $key => $value) {
            $uri = str_replace(":{$key}", (string)$value, $uri);
        }

        // Loại bỏ optional segments còn lại nếu không được cung cấp
        $uri = preg_replace('/\/:[^\/]+/', '', $uri);

        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $base . $uri;
    }


    // =========================================================
    //  INTERNAL HELPERS
    // =========================================================

    private function addRoute(string $method, string $uri, string|callable $handler): static
    {
        $prefix     = $this->currentGroupPrefix();
        $middleware = $this->currentGroupMiddleware();
        $fullUri    = $prefix . ($uri === '/' ? '' : $uri) ?: '/';

        $this->routes[] = [
            'method'       => strtoupper($method),
            'original_uri' => $fullUri,
            'pattern'      => $this->buildPattern($fullUri),
            'param_names'  => $this->extractParamNames($fullUri),
            'handler'      => $handler,
            'middleware'   => $middleware,
            'name'         => null,
            'constraints'  => [],
        ];

        $this->lastIndex = count($this->routes) - 1;
        return $this;
    }

    /**
     * Chuyển URI pattern thành regex
     *   /rooms/:id/edit  →  #^/rooms/(?P<id>[^/]+)/edit$#
     */
    private function buildPattern(string $uri): string
    {
        $pattern = preg_replace_callback(
            '/:([a-zA-Z_][a-zA-Z0-9_]*)/',
            fn($m) => '(?P<' . $m[1] . '>[^/]+)',
            $uri
        );
        return '#^' . $pattern . '$#u';
    }

    private function extractParamNames(string $uri): array
    {
        preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $uri, $matches);
        return $matches[1];
    }

    private function matchRoute(array $route, string $uri, array &$params): bool
    {
        // Áp dụng constraints
        $pattern = $route['pattern'];
        foreach ($route['constraints'] as $param => $regex) {
            $pattern = str_replace(
                "(?P<{$param}>[^/]+)",
                "(?P<{$param}>{$regex})",
                $pattern
            );
        }

        if (!preg_match($pattern, $uri, $matches)) {
            return false;
        }

        // Lấy named captures
        foreach ($route['param_names'] as $name) {
            if (isset($matches[$name])) {
                $params[$name] = $matches[$name];
            }
        }
        return true;
    }

    /**
     * Xử lý handler dạng 'ControllerClass@method' hoặc callable
     */
    private function callHandler(string|callable $handler, array $params): void
    {
        if (is_callable($handler)) {
            $handler($params);
            return;
        }

        // 'RoomController@index'
        [$class, $method] = explode('@', $handler, 2);

        // Auto-load: tìm trong app/controllers/
        if (!class_exists($class)) {
            $file = __DIR__ . "/../controllers/{$class}.php";
            if (!file_exists($file)) {
                throw new \RuntimeException("Controller file không tồn tại: {$file}");
            }
            require_once $file;
        }

        if (!class_exists($class)) {
            throw new \RuntimeException("Class '{$class}' không tồn tại.");
        }
        if (!method_exists($class, $method)) {
            throw new \RuntimeException("Method '{$class}@{$method}' không tồn tại.");
        }

        $controller = new $class();
        $controller->$method($params);
    }

    /**
     * Chạy middleware theo dạng onion (pipeline)
     * Middleware là class có method handle(callable $next): void
     */
    private function runMiddleware(array $middleware, callable $final): void
    {
        // Xây pipeline từ trong ra ngoài
        $pipeline = array_reduce(
            array_reverse($middleware),
            function (callable $carry, string $name): callable {
                return function () use ($name, $carry): void {
                    $mw = $this->resolveMiddleware($name);
                    $mw->handle($carry);
                };
            },
            $final
        );

        $pipeline();
    }

    private function resolveMiddleware(string $name): object
    {
        $map = [
            'auth'    => 'AuthMiddleware',
            'admin'   => 'AdminMiddleware',
            'guest'   => 'GuestMiddleware',
            'api'     => 'ApiMiddleware',
        ];

        $class = $map[$name] ?? $name;
        $file  = __DIR__ . "/../middleware/{$class}.php";

        if (!class_exists($class) && file_exists($file)) {
            require_once $file;
        }

        if (!class_exists($class)) {
            throw new \RuntimeException("Middleware '{$class}' không tồn tại.");
        }

        return new $class();
    }

    /** Xử lý HTTP method override (HTML form chỉ support GET/POST) */
    private function resolveMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // HTML form dùng _method=PUT / DELETE
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }

        // Header X-HTTP-Method-Override (từ AJAX)
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $override = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }

        return $method;
    }

    /** Lấy URI sạch (bỏ query string, base path) */
    private function resolveUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Bỏ query string
        if (str_contains($uri, '?')) {
            $uri = strstr($uri, '?', true);
        }

        // Bỏ base path (khi app chạy trong subfolder /ktx/public)
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        return '/' . trim(urldecode($uri), '/') ?: '/';
    }

    private function handleNotFound(string $uri): void
    {
        http_response_code(404);

        if ($this->isJsonRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'code'    => 404,
                'message' => "Route '{$uri}' không tìm thấy.",
            ]);
            return;
        }

        $view = __DIR__ . '/../views/errors/404.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo "<h1>404 — Trang không tìm thấy</h1><p>URI: {$uri}</p>";
        }
    }

    private function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $ct     = $_SERVER['HTTP_CONTENT_TYPE'] ?? $_SERVER['CONTENT_TYPE'] ?? '';
        $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($ct, 'application/json')
            || strtolower($xhr) === 'xmlhttprequest';
    }

    private function currentGroupPrefix(): string
    {
        return empty($this->groupStack)
            ? ''
            : end($this->groupStack)['prefix'];
    }

    private function currentGroupMiddleware(): array
    {
        return empty($this->groupStack)
            ? []
            : end($this->groupStack)['middleware'];
    }

    /** Debug: liệt kê tất cả routes đã đăng ký */
    public function listRoutes(): array
    {
        return array_map(fn($r) => [
            'method'     => $r['method'],
            'uri'        => $r['original_uri'],
            'handler'    => is_callable($r['handler']) ? '[Closure]' : $r['handler'],
            'middleware' => $r['middleware'],
            'name'       => $r['name'],
        ], $this->routes);
    }
}
