<?php
/**
 * core/BaseModel.php
 * ─────────────────────────────────────────────────────────────
 *  Lớp Model gốc — tất cả Model (UserModel, RoomModel...) kế thừa
 *
 *  Pattern áp dụng:
 *    • Repository Pattern — đóng gói mọi truy vấn liên quan
 *      đến một bảng vào một lớp duy nhất
 *    • Template Method — các Model con override $table, $fillable
 *      và thêm method nghiệp vụ riêng
 *
 *  BaseModel cung cấp sẵn:
 *    all(), find(), create(), update(), delete(), paginate(),
 *    where(), count(), exists()
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

abstract class BaseModel
{
    // ── Subclass phải khai báo ────────────────────────────────
    protected string $table    = '';   // tên bảng MySQL
    protected string $pk       = 'id'; // tên cột primary key

    /**
     * Danh sách cột được phép ghi (mass assignment protection).
     * Subclass khai báo để tránh user truyền cột không mong muốn.
     *
     * Ví dụ trong UserModel:
     *   protected array $fillable = ['username', 'email', 'password_hash', 'role'];
     */
    protected array $fillable  = [];

    // ── DB instance (Singleton) ───────────────────────────────
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();

        if (empty($this->table)) {
            throw new \LogicException(
                static::class . ' phải khai báo thuộc tính $table.'
            );
        }
    }


    // =========================================================
    //  READ
    // =========================================================

    /** Lấy tất cả bản ghi trong bảng. */
    public function all(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        return $this->db->select(
            "SELECT * FROM `{$this->table}` ORDER BY `{$orderBy}` {$dir}"
        );
    }


    /** Lấy bản ghi theo PK. */
    public function find(int $id): ?array
    {
        return $this->db->find($this->table, $id);
    }


    /** Lấy bản ghi theo điều kiện. */
    public function where(string $condition, array $params = []): array
    {
        return $this->db->select(
            "SELECT * FROM `{$this->table}` WHERE {$condition}",
            $params
        );
    }


    /** Lấy một bản ghi theo điều kiện. */
    public function whereFirst(string $condition, array $params = []): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM `{$this->table}` WHERE {$condition} LIMIT 1",
            $params
        );
    }


    /** Đếm số bản ghi thỏa điều kiện. */
    public function count(string $where = '1', array $params = []): int
    {
        return $this->db->count($this->table, $where, $params);
    }


    /** Kiểm tra tồn tại. */
    public function exists(string $where, array $params = []): bool
    {
        return $this->db->exists($this->table, $where, $params);
    }


    /** Phân trang. */
    public function paginate(
        string $where   = '1',
        array  $params  = [],
        int    $page    = 1,
        int    $perPage = 15,
        string $orderBy = 'id',
        string $dir     = 'DESC'
    ): array {
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        $sql = "SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY `{$orderBy}` {$dir}";
        return $this->db->paginate($sql, $params, $page, $perPage);
    }


    // =========================================================
    //  WRITE
    // =========================================================

    /**
     * Tạo bản ghi mới.
     * Chỉ các cột trong $fillable mới được phép INSERT.
     *
     * @return int  ID của bản ghi vừa tạo
     */
    public function create(array $data): int
    {
        $filtered = $this->filterFillable($data);
        if (empty($filtered)) {
            throw new \InvalidArgumentException(
                'Không có dữ liệu hợp lệ để tạo bản ghi trong ' . static::class
            );
        }
        return $this->db->insert($this->table, $filtered);
    }


    /**
     * Cập nhật bản ghi theo PK.
     *
     * @return int  Số hàng bị ảnh hưởng
     */
    public function update(int $id, array $data): int
    {
        $filtered = $this->filterFillable($data);
        if (empty($filtered)) {
            throw new \InvalidArgumentException(
                'Không có dữ liệu hợp lệ để cập nhật trong ' . static::class
            );
        }
        return $this->db->update(
            $this->table,
            $filtered,
            "`{$this->pk}` = ?",
            [$id]
        );
    }


    /**
     * Xóa bản ghi theo PK.
     *
     * @return int  Số hàng bị xóa
     */
    public function delete(int $id): int
    {
        return $this->db->delete(
            $this->table,
            "`{$this->pk}` = ?",
            [$id]
        );
    }


    // =========================================================
    //  INTERNAL
    // =========================================================

    /**
     * Lọc $data, chỉ giữ lại các key có trong $fillable.
     * Nếu $fillable rỗng (chưa khai báo), trả về nguyên $data.
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_filter(
            $data,
            fn($key) => in_array($key, $this->fillable, true),
            ARRAY_FILTER_USE_KEY
        );
    }


    /**
     * Truy cập trực tiếp Database instance từ subclass.
     */
    protected function db(): Database
    {
        return $this->db;
    }
}
