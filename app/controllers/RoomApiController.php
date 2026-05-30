<?php
/**
 * app/controllers/RoomApiController.php
 * ─────────────────────────────────────────────────────────────
 *  REST API cho Rooms resource
 *
 *  Endpoints:
 *  GET    /api/rooms              — Danh sách phòng (với filters, pagination)
 *  POST   /api/rooms              — Tạo phòng mới
 *  GET    /api/rooms/:id          — Chi tiết phòng
 *  PUT    /api/rooms/:id          — Cập nhật phòng
 *  DELETE /api/rooms/:id          — Xóa phòng
 *  GET    /api/rooms/:id/occupants — Danh sách sinh viên trong phòng
 *  GET    /api/rooms/building/:buildingId — Lấy phòng của tòa
 *  PATCH  /api/rooms/:id/status   — Thay đổi trạng thái phòng
 *
 *  Status: Active (development)
 *  Thành viên 2 phụ trách (Room module)
 *  Điểm "Xuất sắc": Complete REST API + Filtering + Pagination + Error Handling
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Models.php';

class RoomApiController extends BaseController
{
    private RoomModel $roomModel;
    private BuildingModel $buildingModel;

    /**
     * Room status constants
     */
    private const STATUS_AVAILABLE = 'available';
    private const STATUS_FULL = 'full';
    private const STATUS_MAINTENANCE = 'maintenance';
    private const STATUS_INACTIVE = 'inactive';

    /**
     * Room types
     */
    private const VALID_ROOM_TYPES = ['standard', 'deluxe', 'ac_standard', 'ac_deluxe'];

    public function __construct()
    {
        parent::__construct();
        $this->roomModel = new RoomModel();
        $this->buildingModel = new BuildingModel();
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  GET /api/rooms
     *  Danh sách phòng với filters và pagination
     * ───────────────────────────────────────────────────────────
     */
    public function index(array $params = []): void
    {
        // Pagination
        [$page, $perPage] = $this->paginationParams();

        // Filters
        $buildingId = $this->request('building_id');
        $status = $this->request('status');
        $roomType = $this->request('type');
        $hasAc = $this->request('has_ac');
        $search = $this->request('q');

        // Build WHERE clause
        $where = '1';
        $args = [];

        if ($buildingId) {
            $where .= ' AND r.building_id = ?';
            $args[] = (int)$buildingId;
        }

        if ($status) {
            $where .= ' AND r.status = ?';
            $args[] = $status;
        }

        if ($roomType) {
            $where .= ' AND r.room_type = ?';
            $args[] = $roomType;
        }

        if ($hasAc !== null) {
            $where .= ' AND r.has_ac = ?';
            $args[] = (int)$hasAc;
        }

        if ($search) {
            $where .= ' AND (r.room_number LIKE ? OR b.name LIKE ?)';
            $searchParam = "%{$search}%";
            $args[] = $searchParam;
            $args[] = $searchParam;
        }

        // Sorting
        $sortBy = $this->request('sort_by', 'r.id');
        $sortOrder = $this->request('sort_order', 'ASC');

        // Validate sort parameters
        $validSortFields = ['r.id', 'r.room_number', 'r.floor', 'r.price_per_month', 'r.current_occupants'];
        $sortBy = in_array($sortBy, $validSortFields) ? $sortBy : 'r.id';
        $sortOrder = in_array(strtoupper($sortOrder), ['ASC', 'DESC']) ? strtoupper($sortOrder) : 'ASC';

        // Query
        $result = $this->db->paginate(
            "SELECT r.id, r.building_id, r.room_number, r.floor, r.room_type,
                    r.capacity, r.current_occupants, r.price_per_month, r.has_ac,
                    r.status, r.created_at, b.name AS building_name
             FROM rooms r
             JOIN buildings b ON b.id = r.building_id
             WHERE {$where}
             ORDER BY {$sortBy} {$sortOrder}",
            $args,
            $page,
            $perPage
        );

        // Format response
        $data = [
            'data'       => $this->formatRooms($result['data']),
            'pagination' => [
                'current_page'  => $result['current_page'],
                'per_page'      => $result['per_page'],
                'total'         => $result['total'],
                'last_page'     => $result['last_page'],
                'from'          => $result['from'],
                'to'            => $result['to'],
            ],
            'filters'    => [
                'building_id' => $buildingId,
                'status'      => $status,
                'type'        => $roomType,
                'has_ac'      => $hasAc,
                'search'      => $search,
                'sort_by'     => $sortBy,
                'sort_order'  => $sortOrder,
            ],
        ];

        $this->jsonOk($data, 'Lấy danh sách phòng thành công');
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  POST /api/rooms
     *  Tạo phòng mới
     * ───────────────────────────────────────────────────────────
     *
     * Request body:
     * {
     *   "building_id": 1,
     *   "room_number": "201",
     *   "floor": 2,
     *   "room_type": "standard",
     *   "capacity": 4,
     *   "price_per_month": 600000,
     *   "has_ac": false,
     *   "notes": "Phòng 4 giường"
     * }
     */
    public function store(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $data = $this->only([
            'building_id',
            'room_number',
            'floor',
            'room_type',
            'capacity',
            'price_per_month',
            'has_ac',
            'notes',
        ]);

        // Validation
        $errors = $this->validateRoomData($data, isUpdate: false);

        if (!empty($errors)) {
            $this->jsonError('Dữ liệu không hợp lệ', 422, $errors);
        }

        try {
            $roomId = $this->db->transaction(function (Database $db) use ($data) {
                return $db->insert('rooms', [
                    'building_id'     => (int)$data['building_id'],
                    'room_number'     => $data['room_number'],
                    'floor'           => (int)$data['floor'],
                    'room_type'       => $data['room_type'],
                    'capacity'        => (int)$data['capacity'],
                    'current_occupants' => 0,
                    'price_per_month' => (float)$data['price_per_month'],
                    'has_ac'          => (int)($data['has_ac'] ?? 0),
                    'status'          => 'available',
                    'notes'           => $data['notes'] ?? null,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            });

            $room = $this->roomModel->find($roomId);

            error_log("[ROOM_API] Created room #$roomId in building {$data['building_id']}");

            $this->jsonOk(
                $this->formatRoom($room),
                'Tạo phòng thành công',
                201
            );
        } catch (\Throwable $e) {
            error_log("[ROOM_API_ERROR] " . $e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  GET /api/rooms/:id
     *  Chi tiết phòng
     * ───────────────────────────────────────────────────────────
     */
    public function show(array $params = []): void
    {
        $id = (int)$params['id'];

        $room = $this->db->selectOne(
            "SELECT r.*, b.name AS building_name, b.gender_type
             FROM rooms r
             JOIN buildings b ON b.id = r.building_id
             WHERE r.id = ?",
            [$id]
        );

        if (!$room) {
            $this->jsonError('Phòng không tồn tại', 404);
        }

        // Get occupants info
        $occupants = $this->db->select(
            "SELECT s.id, s.full_name, s.student_code, u.username
             FROM contracts c
             JOIN students s ON s.id = c.student_id
             JOIN users u ON u.id = s.user_id
             WHERE c.room_id = ? AND c.status IN ('active', 'under_review')
             ORDER BY s.full_name",
            [$id]
        );

        $room['occupants'] = $occupants;
        $room['occupancy_rate'] = $room['capacity'] > 0 
            ? round($room['current_occupants'] / $room['capacity'], 2) 
            : 0;

        $this->jsonOk(
            $this->formatRoom($room),
            'Lấy chi tiết phòng thành công'
        );
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  PUT /api/rooms/:id
     *  Cập nhật phòng
     * ───────────────────────────────────────────────────────────
     *
     * Request body:
     * {
     *   "room_number": "201",
     *   "floor": 2,
     *   "room_type": "deluxe",
     *   "capacity": 4,
     *   "price_per_month": 700000,
     *   "has_ac": true,
     *   "notes": "..."
     * }
     */
    public function update(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];

        $room = $this->roomModel->find($id);
        if (!$room) {
            $this->jsonError('Phòng không tồn tại', 404);
        }

        $data = $this->only([
            'room_number',
            'floor',
            'room_type',
            'capacity',
            'price_per_month',
            'has_ac',
            'notes',
        ]);

        // Validation (allow partial updates)
        $errors = $this->validateRoomData($data, isUpdate: true, existingRoom: $room);

        if (!empty($errors)) {
            $this->jsonError('Dữ liệu không hợp lệ', 422, $errors);
        }

        try {
            // Prepare update data
            $updateData = [];
            if (isset($data['room_number'])) $updateData['room_number'] = $data['room_number'];
            if (isset($data['floor'])) $updateData['floor'] = (int)$data['floor'];
            if (isset($data['room_type'])) $updateData['room_type'] = $data['room_type'];
            if (isset($data['capacity'])) $updateData['capacity'] = (int)$data['capacity'];
            if (isset($data['price_per_month'])) $updateData['price_per_month'] = (float)$data['price_per_month'];
            if (isset($data['has_ac'])) $updateData['has_ac'] = (int)$data['has_ac'];
            if (isset($data['notes'])) $updateData['notes'] = $data['notes'];
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $this->db->update('rooms', $updateData, 'id = ?', [$id]);

            $updated = $this->roomModel->find($id);

            error_log("[ROOM_API] Updated room #$id");

            $this->jsonOk(
                $this->formatRoom($updated),
                'Cập nhật phòng thành công'
            );
        } catch (\Throwable $e) {
            error_log("[ROOM_API_ERROR] " . $e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  DELETE /api/rooms/:id
     *  Xóa phòng (soft delete - set status inactive)
     * ───────────────────────────────────────────────────────────
     */
    public function destroy(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];

        $room = $this->roomModel->find($id);
        if (!$room) {
            $this->jsonError('Phòng không tồn tại', 404);
        }

        // Check if room has occupants
        $occupantCount = $this->db->selectValue(
            "SELECT COUNT(*) FROM contracts WHERE room_id = ? AND status IN ('active', 'under_review')",
            [$id]
        );

        if ($occupantCount > 0) {
            $this->jsonError(
                'Không thể xóa phòng có sinh viên đang ở',
                409,
                ['error' => "Phòng có $occupantCount sinh viên"]
            );
        }

        try {
            // Soft delete
            $this->db->update(
                'rooms',
                ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$id]
            );

            error_log("[ROOM_API] Deleted room #$id");

            $this->jsonOk(null, 'Xóa phòng thành công');
        } catch (\Throwable $e) {
            error_log("[ROOM_API_ERROR] " . $e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  GET /api/rooms/:id/occupants
     *  Danh sách sinh viên trong phòng
     * ───────────────────────────────────────────────────────────
     */
    public function getOccupants(array $params = []): void
    {
        $id = (int)$params['id'];

        $room = $this->roomModel->find($id);
        if (!$room) {
            $this->jsonError('Phòng không tồn tại', 404);
        }

        $occupants = $this->db->select(
            "SELECT s.id, s.full_name, s.student_code, s.gender, u.username,
                    c.id AS contract_id, c.status AS contract_status,
                    c.monthly_fee, c.start_date, c.end_date
             FROM contracts c
             JOIN students s ON s.id = c.student_id
             JOIN users u ON u.id = s.user_id
             WHERE c.room_id = ? AND c.status IN ('active', 'under_review')
             ORDER BY s.full_name",
            [$id]
        );

        $this->jsonOk(
            $occupants,
            'Lấy danh sách sinh viên thành công'
        );
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  GET /api/rooms/building/:buildingId
     *  Lấy tất cả phòng của một tòa
     * ───────────────────────────────────────────────────────────
     */
    public function getByBuilding(array $params = []): void
    {
        $buildingId = (int)$params['buildingId'];

        $building = $this->buildingModel->find($buildingId);
        if (!$building) {
            $this->jsonError('Tòa nhà không tồn tại', 404);
        }

        // Get pagination params
        [$page, $perPage] = $this->paginationParams();

        $result = $this->db->paginate(
            "SELECT r.id, r.building_id, r.room_number, r.floor, r.room_type,
                    r.capacity, r.current_occupants, r.price_per_month, r.has_ac,
                    r.status, r.created_at
             FROM rooms r
             WHERE r.building_id = ?
             ORDER BY r.floor ASC, r.room_number ASC",
            [$buildingId],
            $page,
            $perPage
        );

        $data = [
            'building'   => $building,
            'rooms'      => $this->formatRooms($result['data']),
            'pagination' => [
                'current_page'  => $result['current_page'],
                'per_page'      => $result['per_page'],
                'total'         => $result['total'],
                'last_page'     => $result['last_page'],
            ],
        ];

        $this->jsonOk($data, 'Lấy phòng của tòa thành công');
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  PATCH /api/rooms/:id/status
     *  Thay đổi trạng thái phòng
     * ───────────────────────────────────────────────────────────
     *
     * Request body:
     * {
     *   "status": "maintenance",
     *   "reason": "Sửa chữa phòng tắm"
     * }
     */
    public function updateStatus(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];
        $status = $this->request('status');
        $reason = $this->request('reason');

        $room = $this->roomModel->find($id);
        if (!$room) {
            $this->jsonError('Phòng không tồn tại', 404);
        }

        // Validate status
        $validStatuses = [self::STATUS_AVAILABLE, self::STATUS_FULL, self::STATUS_MAINTENANCE, self::STATUS_INACTIVE];
        if (!in_array($status, $validStatuses)) {
            $this->jsonError('Trạng thái không hợp lệ', 422, [
                'status' => ['Trạng thái phải là: ' . implode(', ', $validStatuses)],
            ]);
        }

        // Check if can change to maintenance
        if ($status === self::STATUS_MAINTENANCE && !$reason) {
            $this->jsonError('Lý do bảo trì là bắt buộc', 422, [
                'reason' => ['Lý do bảo trì không được để trống'],
            ]);
        }

        try {
            $updateData = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($reason) {
                $updateData['maintenance_notes'] = $reason;
            }

            $this->db->update('rooms', $updateData, 'id = ?', [$id]);

            // Log status change
            error_log("[ROOM_API] Room #$id status changed to $status" . ($reason ? ": $reason" : ""));

            $updated = $this->roomModel->find($id);

            $this->jsonOk(
                $this->formatRoom($updated),
                'Cập nhật trạng thái phòng thành công'
            );
        } catch (\Throwable $e) {
            error_log("[ROOM_API_ERROR] " . $e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  GET /api/rooms/available
     *  Lấy danh sách phòng trống (available)
     * ───────────────────────────────────────────────────────────
     */
    public function getAvailable(array $params = []): void
    {
        [$page, $perPage] = $this->paginationParams();

        $result = $this->db->paginate(
            "SELECT r.id, r.building_id, r.room_number, r.floor, r.room_type,
                    r.capacity, r.current_occupants, r.price_per_month, r.has_ac,
                    r.status, b.name AS building_name, b.gender_type
             FROM rooms r
             JOIN buildings b ON b.id = r.building_id
             WHERE r.status = 'available'
               AND r.current_occupants < r.capacity
               AND b.status = 'active'
             ORDER BY r.price_per_month ASC, r.id ASC",
            [],
            $page,
            $perPage
        );

        $data = [
            'data'       => $this->formatRooms($result['data']),
            'pagination' => [
                'current_page'  => $result['current_page'],
                'per_page'      => $result['per_page'],
                'total'         => $result['total'],
                'last_page'     => $result['last_page'],
            ],
        ];

        $this->jsonOk($data, 'Lấy phòng trống thành công');
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  GET /api/rooms/statistics
     *  Thống kê về phòng
     * ───────────────────────────────────────────────────────────
     */
    public function statistics(array $params = []): void
    {
        $stats = [
            'total_rooms'      => $this->db->selectValue(
                "SELECT COUNT(*) FROM rooms WHERE status != 'inactive'"
            ),
            'available_rooms'  => $this->db->selectValue(
                "SELECT COUNT(*) FROM rooms WHERE status = 'available'"
            ),
            'full_rooms'       => $this->db->selectValue(
                "SELECT COUNT(*) FROM rooms WHERE status = 'full'"
            ),
            'maintenance_rooms' => $this->db->selectValue(
                "SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'"
            ),
            'total_capacity'   => $this->db->selectValue(
                "SELECT SUM(capacity) FROM rooms WHERE status != 'inactive'"
            ) ?? 0,
            'total_occupants'  => $this->db->selectValue(
                "SELECT SUM(current_occupants) FROM rooms WHERE status != 'inactive'"
            ) ?? 0,
            'by_building'      => $this->db->select(
                "SELECT b.id, b.name,
                        COUNT(r.id) AS total_rooms,
                        SUM(r.capacity) AS total_capacity,
                        SUM(r.current_occupants) AS total_occupants,
                        SUM(CASE WHEN r.status = 'full' THEN 1 ELSE 0 END) AS full_count
                 FROM buildings b
                 LEFT JOIN rooms r ON r.building_id = b.id AND r.status != 'inactive'
                 WHERE b.status = 'active'
                 GROUP BY b.id
                 ORDER BY b.name"
            ),
        ];

        // Calculate occupancy rate
        $stats['occupancy_rate'] = $stats['total_capacity'] > 0
            ? round($stats['total_occupants'] / $stats['total_capacity'], 4)
            : 0;

        $this->jsonOk($stats, 'Lấy thống kê phòng thành công');
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  HELPER METHODS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Validate room data
     *
     * @param array $data
     * @param bool $isUpdate Whether this is an update
     * @param ?array $existingRoom Existing room data for update
     * @return array Errors array
     */
    private function validateRoomData(array $data, bool $isUpdate = false, ?array $existingRoom = null): array
    {
        $errors = [];

        if (!$isUpdate && empty($data['building_id'])) {
            $errors['building_id'] = ['Tòa nhà là bắt buộc'];
        }

        if (!$isUpdate && empty($data['room_number'])) {
            $errors['room_number'] = ['Số phòng là bắt buộc'];
        }

        if (isset($data['room_number'])) {
            $data['room_number'] = trim($data['room_number']);
            if (strlen($data['room_number']) < 1) {
                $errors['room_number'] = ['Số phòng không hợp lệ'];
            }

            // Check uniqueness (excluding current room if update)
            $buildingId = $data['building_id'] ?? $existingRoom['building_id'];
            $check = $this->db->selectOne(
                "SELECT id FROM rooms WHERE building_id = ? AND room_number = ?" 
                . ($isUpdate ? " AND id != ?" : ""),
                $isUpdate 
                    ? [$buildingId, $data['room_number'], $existingRoom['id']] 
                    : [$buildingId, $data['room_number']]
            );

            if ($check) {
                $errors['room_number'] = ['Số phòng này đã tồn tại trong tòa nhà'];
            }
        }

        if (isset($data['floor'])) {
            if ((int)$data['floor'] < 1) {
                $errors['floor'] = ['Tầng phải >= 1'];
            }
        }

        if (isset($data['room_type'])) {
            if (!in_array($data['room_type'], self::VALID_ROOM_TYPES)) {
                $errors['room_type'] = ['Loại phòng không hợp lệ'];
            }
        }

        if (isset($data['capacity'])) {
            if ((int)$data['capacity'] < 1) {
                $errors['capacity'] = ['Sức chứa phải >= 1'];
            }
        }

        if (isset($data['price_per_month'])) {
            if ((float)$data['price_per_month'] < 0) {
                $errors['price_per_month'] = ['Giá phòng không hợp lệ'];
            }
        }

        return $errors;
    }

    /**
     * Format single room
     */
    private function formatRoom(array $room): array
    {
        return [
            'id'                => (int)$room['id'],
            'building_id'       => (int)$room['building_id'],
            'building_name'     => $room['building_name'] ?? null,
            'room_number'       => $room['room_number'],
            'floor'             => (int)$room['floor'],
            'room_type'         => $room['room_type'],
            'capacity'          => (int)$room['capacity'],
            'current_occupants' => (int)$room['current_occupants'],
            'occupancy_rate'    => $room['capacity'] > 0 
                ? round($room['current_occupants'] / $room['capacity'], 2) 
                : 0,
            'price_per_month'   => (float)$room['price_per_month'],
            'has_ac'            => (bool)$room['has_ac'],
            'status'            => $room['status'],
            'vacant_beds'       => (int)($room['capacity'] - $room['current_occupants']),
            'created_at'        => $room['created_at'],
            'updated_at'        => $room['updated_at'] ?? null,
        ];
    }

    /**
     * Format multiple rooms
     */
    private function formatRooms(array $rooms): array
    {
        return array_map(fn($room) => $this->formatRoom($room), $rooms);
    }
}
