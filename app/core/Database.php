<?php
/**
 * core/Database.php
 * ─────────────────────────────────────────────────────────────
 *  Singleton Pattern — kết nối PDO duy nhất cho toàn ứng dụng
 *
 *  Pattern áp dụng:
 *    • Singleton  — đảm bảo chỉ có 1 instance PDO tồn tại
 *    • Fluent API — các method trả về $this để chain nếu cần
 *
 *  Tính năng:
 *    • Prepared statements chống SQL Injection
 *    • Transaction helpers (begin / commit / rollback)
 *    • Query helpers (select, selectOne, insert, update, delete)
 *    • lastInsertId(), rowCount()
 *    • Error handling tập trung — ném DatabaseException
 *    • Debug mode: log slow queries (APP_DEBUG = true)
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

// ── Custom exception ──────────────────────────────────────────
class DatabaseException extends RuntimeException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        // Log lỗi vào file thay vì expose ra browser
        error_log('[DatabaseException] ' . $message);
    }
}


// ─────────────────────────────────────────────────────────────
class Database
{
    // ── Singleton instance ────────────────────────────────────
    private static ?Database $instance = null;

    // ── PDO connection ────────────────────────────────────────
    private PDO $pdo;

    // ── Prepared statement cache (tránh prepare() lặp lại) ───
    private array $stmtCache = [];

    // ── Query counter (debug) ─────────────────────────────────
    private int   $queryCount = 0;
    private float $totalTime  = 0.0;


    // ─────────────────────────────────────────────────────────
    //  Constructor private — chỉ gọi được qua getInstance()
    // ─────────────────────────────────────────────────────────
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            // Ném PDOException thay vì trả về false
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Trả về array kết hợp theo tên cột mặc định
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Không emulate prepared statements — dùng thật sự của MySQL
            PDO::ATTR_EMULATE_PREPARES   => false,

            // Giữ kiểu dữ liệu gốc (int thành int, không thành string)
            PDO::ATTR_STRINGIFY_FETCHES  => false,

            // Kết nối lại nếu mất kết nối
            PDO::ATTR_PERSISTENT         => false,

            // Timeout kết nối 5 giây
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Không expose thông tin kết nối ra ngoài
            throw new DatabaseException(
                APP_DEBUG
                    ? 'Kết nối database thất bại: ' . $e->getMessage()
                    : 'Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.',
                (int)$e->getCode(),
                $e
            );
        }
    }


    // ─────────────────────────────────────────────────────────
    //  Ngăn clone và unserialize — phá vỡ Singleton
    // ─────────────────────────────────────────────────────────
    private function __clone() {}

    public function __wakeup()
    {
        throw new DatabaseException('Không thể unserialize Singleton Database.');
    }


    // ─────────────────────────────────────────────────────────
    //  getInstance() — điểm truy cập duy nhất
    //
    //  Cách dùng:
    //    $db = Database::getInstance();
    // ─────────────────────────────────────────────────────────
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }


    // ─────────────────────────────────────────────────────────
    //  Truy cập PDO thô (dùng khi cần tính năng nâng cao)
    // ─────────────────────────────────────────────────────────
    public function getPdo(): PDO
    {
        return $this->pdo;
    }


    // =========================================================
    //  CORE: prepare + execute với cache
    // =========================================================

    /**
     * Thực thi một câu SQL với tham số binding.
     *
     * @param string $sql    Câu SQL có placeholder (?, :name)
     * @param array  $params Mảng tham số
     * @return PDOStatement
     *
     * Ví dụ:
     *   $stmt = $db->query("SELECT * FROM users WHERE id = ?", [1]);
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $startTime = microtime(true);

        try {
            // Dùng cache nếu cùng SQL đã prepare rồi
            $cacheKey = md5($sql);
            if (!isset($this->stmtCache[$cacheKey])) {
                $this->stmtCache[$cacheKey] = $this->pdo->prepare($sql);
            }

            $stmt = $this->stmtCache[$cacheKey];
            $stmt->execute($params);

            $elapsed = microtime(true) - $startTime;
            $this->queryCount++;
            $this->totalTime += $elapsed;

            // Cảnh báo slow query > 1 giây (debug mode)
            if (APP_DEBUG && $elapsed > 1.0) {
                error_log(sprintf(
                    '[SlowQuery %.3fs] %s | params: %s',
                    $elapsed,
                    $sql,
                    json_encode($params)
                ));
            }

            return $stmt;

        } catch (PDOException $e) {
            throw new DatabaseException(
                APP_DEBUG
                    ? "Query thất bại: {$e->getMessage()} | SQL: {$sql}"
                    : 'Lỗi truy vấn cơ sở dữ liệu.',
                (int)$e->getCode(),
                $e
            );
        }
    }


    // =========================================================
    //  SELECT HELPERS
    // =========================================================

    /**
     * Lấy tất cả hàng kết quả.
     *
     * @return array<int, array<string, mixed>>
     *
     * Ví dụ:
     *   $rooms = $db->select("SELECT * FROM rooms WHERE status = ?", ['available']);
     */
    public function select(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }


    /**
     * Lấy một hàng duy nhất.
     *
     * @return array<string, mixed>|null  null nếu không tìm thấy
     *
     * Ví dụ:
     *   $user = $db->selectOne("SELECT * FROM users WHERE email = ?", [$email]);
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result !== false ? $result : null;
    }


    /**
     * Lấy giá trị của một cột trong một hàng.
     *
     * Ví dụ:
     *   $count = $db->selectValue("SELECT COUNT(*) FROM students");
     *   $name  = $db->selectValue("SELECT full_name FROM students WHERE id = ?", [1]);
     */
    public function selectValue(string $sql, array $params = []): mixed
    {
        $result = $this->query($sql, $params)->fetch(PDO::FETCH_NUM);
        return $result !== false ? $result[0] : null;
    }


    // =========================================================
    //  INSERT / UPDATE / DELETE HELPERS
    // =========================================================

    /**
     * INSERT một hàng vào bảng.
     *
     * @param string               $table  Tên bảng
     * @param array<string, mixed> $data   ['column' => value, ...]
     * @return int  lastInsertId
     *
     * Ví dụ:
     *   $id = $db->insert('users', [
     *       'username'      => 'sv001',
     *       'email'         => 'sv001@ktx.edu.vn',
     *       'password_hash' => password_hash('123456', PASSWORD_BCRYPT),
     *       'role'          => 'student',
     *   ]);
     */
    public function insert(string $table, array $data): int
    {
        $this->validateTableName($table);
        $columns      = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        $this->query($sql, array_values($data));
        return (int)$this->pdo->lastInsertId();
    }


    /**
     * UPDATE các hàng khớp với điều kiện WHERE.
     *
     * @param string               $table      Tên bảng
     * @param array<string, mixed> $data       Dữ liệu cần cập nhật
     * @param string               $where      Điều kiện WHERE (không có từ WHERE)
     * @param array                $whereParams Tham số cho WHERE
     * @return int  Số hàng bị ảnh hưởng
     *
     * Ví dụ:
     *   $affected = $db->update('rooms', ['status' => 'full'], 'id = ?', [3]);
     */
    public function update(
        string $table,
        array  $data,
        string $where,
        array  $whereParams = []
    ): int {
        $this->validateTableName($table);
        $setClauses = array_map(
            fn($col) => "`{$col}` = ?",
            array_keys($data)
        );

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $setClauses),
            $where
        );

        return $this->query($sql, [...array_values($data), ...$whereParams])
                    ->rowCount();
    }


    /**
     * DELETE các hàng khớp với điều kiện WHERE.
     *
     * @return int  Số hàng bị xóa
     *
     * Ví dụ:
     *   $deleted = $db->delete('notifications', 'user_id = ? AND is_read = 1', [5]);
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $this->validateTableName($table);
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, $where);
        return $this->query($sql, $params)->rowCount();
    }


    // =========================================================
    //  TRANSACTION HELPERS
    // =========================================================

    /**
     * Bắt đầu transaction.
     *
     * Ví dụ:
     *   $db->beginTransaction();
     *   try {
     *       $db->insert(...);
     *       $db->update(...);
     *       $db->commit();
     *   } catch (Exception $e) {
     *       $db->rollback();
     *       throw $e;
     *   }
     */
    public function beginTransaction(): void
    {
        if ($this->pdo->inTransaction()) {
            throw new DatabaseException('Transaction đang hoạt động, không thể bắt đầu transaction mới.');
        }
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        if (!$this->pdo->inTransaction()) {
            throw new DatabaseException('Không có transaction nào đang hoạt động để commit.');
        }
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }


    /**
     * Thực thi một callable trong transaction — tự động commit/rollback.
     *
     * @param callable $callback  function(Database $db): mixed
     * @return mixed  Giá trị trả về từ callback
     *
     * Ví dụ (tạo hợp đồng + cập nhật phòng trong 1 transaction):
     *   $result = $db->transaction(function(Database $db) use ($data) {
     *       $contractId = $db->insert('contracts', $data['contract']);
     *       $db->update('rooms', ['status' => 'full'], 'id = ?', [$data['room_id']]);
     *       return $contractId;
     *   });
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }


    // =========================================================
    //  CONVENIENCE METHODS
    // =========================================================

    /**
     * ID của hàng vừa INSERT.
     */
    public function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }


    /**
     * Kiểm tra một hàng có tồn tại không.
     *
     * Ví dụ:
     *   if ($db->exists('users', 'email = ?', [$email])) { ... }
     */
    public function exists(string $table, string $where, array $params = []): bool
    {
        $this->validateTableName($table);
        $sql    = "SELECT 1 FROM `{$table}` WHERE {$where} LIMIT 1";
        $result = $this->query($sql, $params)->fetch(PDO::FETCH_NUM);
        return $result !== false;
    }


    /**
     * Đếm số hàng thỏa điều kiện.
     *
     * Ví dụ:
     *   $total = $db->count('rooms', 'building_id = ? AND status = ?', [1, 'available']);
     */
    public function count(string $table, string $where = '1', array $params = []): int
    {
        $this->validateTableName($table);
        $sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where}";
        return (int)$this->selectValue($sql, $params);
    }


    /**
     * Lấy một hàng theo PK (id).
     *
     * Ví dụ:
     *   $room = $db->find('rooms', 3);
     */
    public function find(string $table, int $id): ?array
    {
        $this->validateTableName($table);
        return $this->selectOne(
            "SELECT * FROM `{$table}` WHERE id = ? LIMIT 1",
            [$id]
        );
    }


    /**
     * INSERT hoặc UPDATE nếu đã tồn tại (MySQL ON DUPLICATE KEY UPDATE).
     *
     * Ví dụ — ghi chỉ số điện nước (unique: room_id + month + year):
     *   $db->upsert('utility_readings', $data, ['elec_curr', 'water_curr', 'recorded_by']);
     */
    public function upsert(string $table, array $data, array $updateColumns): int
    {
        $this->validateTableName($table);
        $columns      = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $updateParts  = array_map(fn($c) => "`{$c}` = VALUES(`{$c}`)", $updateColumns);

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders),
            implode(', ', $updateParts)
        );

        $this->query($sql, array_values($data));
        return (int)$this->pdo->lastInsertId();
    }


    // =========================================================
    //  PAGINATION
    // =========================================================

    /**
     * SELECT với phân trang.
     *
     * @return array{
     *   data:         array,
     *   total:        int,
     *   per_page:     int,
     *   current_page: int,
     *   last_page:    int,
     *   from:         int,
     *   to:           int,
     * }
     *
     * Ví dụ:
     *   $result = $db->paginate(
     *       "SELECT * FROM students WHERE faculty = ?",
     *       ['Công nghệ thông tin'],
     *       $page, $perPage
     *   );
     *   // $result['data']      — mảng sinh viên trang hiện tại
     *   // $result['last_page'] — tổng số trang
     */
    public function paginate(
        string $sql,
        array  $params   = [],
        int    $page     = 1,
        int    $perPage  = 15
    ): array {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));  // giới hạn 100 rows/page

        // Đếm tổng (bọc trong subquery để đảm bảo chính xác với GROUP BY)
        $countSql = "SELECT COUNT(*) FROM ({$sql}) AS _count_query";
        $total    = (int)$this->selectValue($countSql, $params);

        // Lấy dữ liệu trang
        $offset   = ($page - 1) * $perPage;
        $dataSql  = "{$sql} LIMIT {$perPage} OFFSET {$offset}";
        $data     = $this->select($dataSql, $params);

        $lastPage = (int)ceil($total / $perPage) ?: 1;

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }


    // =========================================================
    //  DEBUG / PROFILING (chỉ dùng trong development)
    // =========================================================

    /**
     * Trả về thống kê số query và thời gian thực thi.
     *
     * Ví dụ:
     *   var_dump(Database::getInstance()->getStats());
     */
    public function getStats(): array
    {
        return [
            'query_count'     => $this->queryCount,
            'total_time_ms'   => round($this->totalTime * 1000, 2),
            'avg_time_ms'     => $this->queryCount > 0
                ? round(($this->totalTime / $this->queryCount) * 1000, 2)
                : 0,
            'stmt_cache_size' => count($this->stmtCache),
        ];
    }


    // =========================================================
    //  SECURITY HELPER (nội bộ)
    // =========================================================

    /**
     * Kiểm tra tên bảng chỉ chứa ký tự an toàn.
     * Ngăn SQL injection qua tên bảng (không thể dùng placeholder cho table name).
     */
    private function validateTableName(string $table): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new DatabaseException("Tên bảng không hợp lệ: '{$table}'");
        }
    }
}
