<?php
/**
 * app/services/RoomAllocationService.php
 * ─────────────────────────────────────────────────────────────
 *  Tự động gán phòng KTX dựa vào:
 *  • Giới tính (gender) của sinh viên
 *  • Giới tính tòa nhà (gender_type: male/female/mixed)
 *  • Mức độ ưu tiên chính sách (priority_level: 0=normal, 1=policy, 2=high)
 *  • Lấp đầy phòng (prefer fuller rooms for policy students)
 *  • Giá phòng (prefer cheaper for normal students)
 *
 *  Thành viên 2 phụ trách (Room module)
 *  Điểm "Xuất sắc": Business logic + Transaction + Complexity
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Models.php';

class RoomAllocationService
{
    private Database $db;
    private RoomModel $roomModel;
    private StudentModel $studentModel;

    /**
     * Priority levels
     */
    private const PRIORITY_NORMAL = 0;      // Bình thường
    private const PRIORITY_POLICY = 1;      // Chính sách (ưu tiên)
    private const PRIORITY_HIGH = 2;        // Ưu tiên cao

    /**
     * Gender types
     */
    private const GENDER_MALE = 'male';
    private const GENDER_FEMALE = 'female';
    private const BUILDING_MIXED = 'mixed';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->roomModel = new RoomModel();
        $this->studentModel = new StudentModel();
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  MAIN ALLOCATION FUNCTION
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Tự động gán phòng cho sinh viên
     *
     * @param int $studentId      ID của sinh viên
     * @param int $registrationId ID của đơn đăng ký
     * @return array {
     *     "success": bool,
     *     "message": string,
     *     "data": { "room_id": int, "room_number": string, ... } | null
     * }
     *
     * Quy trình:
     *   1. Lấy thông tin sinh viên (giới tính, ưu tiên)
     *   2. Kiểm tra sinh viên có thể đăng ký không
     *   3. Lấy danh sách tòa nhà phù hợp với giới tính
     *   4. Lấy danh sách phòng có chỗ trống
     *   5. Sắp xếp theo quy tắc ưu tiên
     *   6. Chọn phòng tốt nhất
     *   7. Tạo hợp đồng (trong transaction)
     *   8. Gửi thông báo
     */
    public function allocateRoom(int $studentId, int $registrationId): array
    {
        // ─── Step 1: Lấy thông tin sinh viên ────────────────
        $student = $this->studentModel->find($studentId);

        if (!$student) {
            return $this->error('Sinh viên không tồn tại');
        }

        // ─── Step 2: Kiểm tra điều kiện ────────────────────
        $validation = $this->validateAllocation($student);
        if (!$validation['valid']) {
            return $this->error($validation['message']);
        }

        // ─── Step 3-4: Lấy danh sách phòng phù hợp ─────────
        $availableRooms = $this->getAvailableRooms($student);

        if (empty($availableRooms)) {
            return $this->error(
                'Hiện không có phòng trống phù hợp. Vui lòng thử lại sau.'
            );
        }

        // ─── Step 5: Sắp xếp theo quy tắc ưu tiên ─────────
        $sortedRooms = $this->rankRooms($availableRooms, $student);

        // ─── Step 6: Chọn phòng tốt nhất ───────────────────
        $selectedRoom = reset($sortedRooms);

        if (!$selectedRoom) {
            return $this->error('Không thể chọn phòng');
        }

        // ─── Step 7-8: Tạo hợp đồng trong transaction ──────
        try {
            $contractId = $this->createContractSafely(
                $studentId,
                $registrationId,
                (int)$selectedRoom['id'],
                (float)$selectedRoom['price_per_month']
            );

            return $this->success([
                'room_id'       => $selectedRoom['id'],
                'room_number'   => $selectedRoom['room_number'],
                'building_name' => $selectedRoom['building_name'],
                'floor'         => $selectedRoom['floor'],
                'room_type'     => $selectedRoom['room_type'],
                'capacity'      => $selectedRoom['capacity'],
                'price_per_month' => $selectedRoom['price_per_month'],
                'has_ac'        => $selectedRoom['has_ac'],
                'contract_id'   => $contractId,
            ], 'Gán phòng thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return $this->error('Lỗi khi tạo hợp đồng: ' . $e->getMessage());
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  VALIDATION
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Kiểm tra sinh viên có thể đăng ký phòng không
     *
     * @return array { "valid": bool, "message": string }
     */
    private function validateAllocation(array $student): array
    {
        $userId = (int)$student['user_id'];

        // 1. Kiểm tra tài khoản hoạt động
        $user = $this->db->selectOne(
            "SELECT status FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user || $user['status'] !== 'active') {
            return [
                'valid'   => false,
                'message' => 'Tài khoản của bạn không hoạt động',
            ];
        }

        // 2. Kiểm tra chưa có hợp đồng active
        $activeContract = $this->db->selectOne(
            "SELECT id FROM contracts
             WHERE student_id = ? AND status = 'active'",
            [$student['id']]
        );

        if ($activeContract) {
            return [
                'valid'   => false,
                'message' => 'Bạn đã có hợp đồng phòng đang hoạt động',
            ];
        }

        // 3. Kiểm tra không bị cấm (violation points >= threshold)
        $totalPoints = $this->db->selectValue(
            "SELECT COALESCE(SUM(penalty_points), 0)
             FROM violation_records
             WHERE student_id = ? AND status = 'active'",
            [$student['id']]
        );

        if ($totalPoints >= VIOLATION_THRESHOLD) {
            return [
                'valid'   => false,
                'message' => 'Bạn đã vượt quá số điểm vi phạm, không được phép đăng ký',
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  GET AVAILABLE ROOMS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Lấy danh sách phòng có chỗ trống và phù hợp với sinh viên
     *
     * @return array[] Danh sách phòng trống
     */
    private function getAvailableRooms(array $student): array
    {
        $gender = $student['gender'] ?? 'male';

        // Lấy tất cả phòng còn chỗ trống
        // và tòa nhà phù hợp với giới tính
        return $this->db->select(
            "SELECT r.*, b.name AS building_name, b.gender_type
             FROM rooms r
             JOIN buildings b ON b.id = r.building_id
             WHERE r.status = 'available'
               AND r.current_occupants < r.capacity
               AND b.status = 'active'
               AND (b.gender_type = ? OR b.gender_type = ?)
             ORDER BY r.id",
            [$gender, self::BUILDING_MIXED]
        );
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  RANKING ALGORITHM
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Sắp xếp phòng theo quy tắc ưu tiên
     *
     * Quy tắc:
     *   • Sinh viên ưu tiên (priority > 0): ưu tiên phòng đầy hơn
     *     → Giúp tối ưu hóa sử dụng phòng
     *     → Policy students: phòng càng đầy càng tốt
     *
     *   • Sinh viên thường (priority = 0): ưu tiên phòng rẻ hơn
     *     → Normal students: price ascending
     *
     *   • Tie-break: giới tính tòa nhà
     *     → Single-gender > mixed
     *
     * @return array[] Danh sách phòng đã sắp xếp
     */
    private function rankRooms(array $rooms, array $student): array
    {
        $priority = (int)($student['priority_level'] ?? 0);

        // Thêm score cho từng phòng
        $scored = array_map(function ($room) use ($priority) {
            return $this->scoreRoom($room, $priority);
        }, $rooms);

        // Sắp xếp theo score (cao nhất trước)
        usort($scored, function ($a, $b) {
            // Score chính (priority logic)
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            // Tie-break 1: Prefer single-gender buildings
            $genderA = $a['gender_type'] === 'mixed' ? 0 : 1;
            $genderB = $b['gender_type'] === 'mixed' ? 0 : 1;

            if ($genderA !== $genderB) {
                return $genderB <=> $genderA;
            }

            // Tie-break 2: Prefer lower room ID (more consistent)
            return $a['id'] <=> $b['id'];
        });

        return $scored;
    }

    /**
     * Tính điểm (score) cho một phòng
     *
     * Score dựa vào:
     *   • Priority level của sinh viên
     *   • Tình trạng lấp đầy của phòng
     *   • Giá phòng
     *
     * @return float Score (0-1000)
     */
    private function scoreRoom(array $room, int $priority): array
    {
        $occupancyRate = (float)$room['current_occupants'] / (float)$room['capacity'];

        if ($priority >= self::PRIORITY_POLICY) {
            // Policy students: ưu tiên phòng đầy (consolidate occupancy)
            // Score = occupancy rate (0-1) * 500 + random (0-100)
            $score = ($occupancyRate * 500) + rand(0, 100);
        } else {
            // Normal students: ưu tiên phòng rẻ
            // Score = (1 - price/max_price) * 500 + occupancy bonus
            $maxPrice = 1000000; // VND
            $priceScore = ((1 - min($room['price_per_month'] / $maxPrice, 1)) * 500);
            $occupancyBonus = ($occupancyRate * 100); // slight bonus for fuller rooms
            $score = $priceScore + $occupancyBonus + rand(0, 50);
        }

        $room['score'] = $score;
        return $room;
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  CONTRACT CREATION
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Tạo hợp đồng trong transaction (an toàn)
     *
     * Steps:
     *   1. Cập nhật đơn đăng ký → approved
     *   2. Tạo hợp đồng
     *   3. Tăng current_occupants của phòng
     *   4. Cập nhật room status nếu đầy
     *   5. Gửi thông báo cho sinh viên
     *   6. Ghi log
     *
     * @return int Contract ID
     */
    private function createContractSafely(
        int $studentId,
        int $registrationId,
        int $roomId,
        float $monthlyFee
    ): int {
        return $this->db->transaction(function (Database $db) use (
            $studentId,
            $registrationId,
            $roomId,
            $monthlyFee
        ) {
            // 1. Cập nhật đơn đăng ký
            $db->update(
                'room_registrations',
                [
                    'status'      => 'approved',
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'reviewed_by' => null, // auto-approved
                ],
                'id = ?',
                [$registrationId]
            );

            // 2. Tạo hợp đồng
            $room = $db->find('rooms', $roomId);
            $contractId = $db->insert('contracts', [
                'registration_id' => $registrationId,
                'student_id'      => $studentId,
                'room_id'         => $roomId,
                'start_date'      => date('Y-m-d'), // Hôm nay
                'end_date'        => date('Y-m-d', strtotime('+9 months')), // 9 tháng
                'monthly_fee'     => $monthlyFee,
                'status'          => 'active',
                'signed_at'       => date('Y-m-d H:i:s'),
            ]);

            // 3. Tăng occupants
            $db->update(
                'rooms',
                ['current_occupants' => new \PDOStatement()], // raw SQL
                'id = ?',
                [$roomId]
            );

            // Alternative: use raw query for increment
            $db->query(
                "UPDATE rooms SET current_occupants = current_occupants + 1 WHERE id = ?",
                [$roomId]
            );

            // 4. Cập nhật room status
            $newOccupancy = $room['current_occupants'] + 1;
            if ($newOccupancy >= $room['capacity']) {
                $db->update('rooms', ['status' => 'full'], 'id = ?', [$roomId]);
            }

            // 5. Gửi thông báo
            $student = $db->selectOne(
                "SELECT s.user_id, s.full_name FROM students WHERE id = ?",
                [$studentId]
            );

            if ($student) {
                $db->insert('notifications', [
                    'user_id' => $student['user_id'],
                    'title'   => 'Gán phòng KTX thành công',
                    'message' => "Chúc mừng {$student['full_name']}! "
                                . "Bạn đã được gán phòng thành công. "
                                . "Vui lòng kiểm tra chi tiết hợp đồng.",
                    'type'    => 'registration',
                ]);
            }

            // 6. Ghi log
            error_log(sprintf(
                '[ALLOCATION] Student #%d allocated to room #%d (Contract #%d)',
                $studentId,
                $roomId,
                $contractId
            ));

            return $contractId;
        });
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  MANUAL ALLOCATION (Admin override)
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Admin gán phòng thủ công cho sinh viên
     *
     * @param int $studentId
     * @param int $roomId
     * @param int $registrationId
     * @return array Result
     */
    public function allocateRoomManual(
        int $studentId,
        int $roomId,
        int $registrationId
    ): array {
        // Kiểm tra phòng còn chỗ trống
        $room = $this->db->selectOne(
            "SELECT * FROM rooms WHERE id = ? AND status != 'inactive'",
            [$roomId]
        );

        if (!$room) {
            return $this->error('Phòng không tồn tại hoặc không khả dụng');
        }

        if ($room['current_occupants'] >= $room['capacity']) {
            return $this->error('Phòng đã đầy');
        }

        // Kiểm tra sinh viên
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            return $this->error('Sinh viên không tồn tại');
        }

        // Kiểm tra giới tính
        $building = $this->db->selectOne(
            "SELECT gender_type FROM buildings WHERE id = ?",
            [$room['building_id']]
        );

        if ($building && $building['gender_type'] !== 'mixed' 
            && $building['gender_type'] !== $student['gender']) {
            return $this->error(
                "Sinh viên {$student['gender']} không thể vào tòa nhà "
                . "{$building['gender_type']}"
            );
        }

        try {
            $contractId = $this->createContractSafely(
                $studentId,
                $registrationId,
                $roomId,
                (float)$room['price_per_month']
            );

            return $this->success([
                'contract_id' => $contractId,
                'room_id'     => $roomId,
            ], 'Gán phòng thủ công thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  STATISTICS & REPORTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Lấy thống kê tình trạng lấp đầy
     *
     * @return array {
     *     "total_rooms": int,
     *     "available_rooms": int,
     *     "full_rooms": int,
     *     "maintenance_rooms": int,
     *     "occupancy_rate": float (0-1),
     *     "by_building": [ ... ]
     * }
     */
    public function getOccupancyStats(): array
    {
        $total = $this->db->selectValue("SELECT COUNT(*) FROM rooms WHERE status != 'inactive'");
        $available = $this->db->selectValue("SELECT COUNT(*) FROM rooms WHERE status = 'available'");
        $full = $this->db->selectValue("SELECT COUNT(*) FROM rooms WHERE status = 'full'");
        $maintenance = $this->db->selectValue("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'");

        // Tổng giường
        $totalBeds = $this->db->selectValue(
            "SELECT SUM(capacity) FROM rooms WHERE status != 'inactive'"
        ) ?? 0;
        $occupiedBeds = $this->db->selectValue(
            "SELECT SUM(current_occupants) FROM rooms WHERE status != 'inactive'"
        ) ?? 0;

        $occupancyRate = $totalBeds > 0 ? $occupiedBeds / $totalBeds : 0;

        // Thống kê theo tòa nhà
        $byBuilding = $this->db->select(
            "SELECT b.id, b.name, 
                    COUNT(r.id) AS total_rooms,
                    SUM(r.capacity) AS total_beds,
                    SUM(r.current_occupants) AS occupied_beds,
                    SUM(CASE WHEN r.status = 'full' THEN 1 ELSE 0 END) AS full_rooms,
                    ROUND(SUM(r.current_occupants) / SUM(r.capacity), 2) AS occupancy_rate
             FROM buildings b
             LEFT JOIN rooms r ON r.building_id = b.id AND r.status != 'inactive'
             GROUP BY b.id
             ORDER BY b.name"
        );

        return [
            'total_rooms'       => (int)$total,
            'available_rooms'   => (int)$available,
            'full_rooms'        => (int)$full,
            'maintenance_rooms' => (int)$maintenance,
            'total_beds'        => (int)$totalBeds,
            'occupied_beds'     => (int)$occupiedBeds,
            'occupancy_rate'    => round($occupancyRate, 4),
            'by_building'       => $byBuilding,
        ];
    }

    /**
     * Báo cáo phòng trống
     *
     * @return array[]
     */
    public function getAvailableRoomsReport(): array
    {
        return $this->db->select(
            "SELECT r.id, r.room_number, r.floor, r.room_type,
                    r.capacity, r.current_occupants, r.price_per_month,
                    b.name AS building_name, b.gender_type,
                    (r.capacity - r.current_occupants) AS vacant_beds
             FROM rooms r
             JOIN buildings b ON b.id = r.building_id
             WHERE r.status = 'available'
               AND b.status = 'active'
             ORDER BY b.name, r.floor, r.room_number"
        );
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  HELPER METHODS
     * ───────────────────────────────────────────────────────────
     */

    private function success(mixed $data = null, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];
    }

    private function error(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'data'    => null,
        ];
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  UTILITY: Check if room matches student gender
     * ───────────────────────────────────────────────────────────
     */

    public function isRoomSuitableForStudent(array $room, array $student): bool
    {
        $building = $this->db->selectOne(
            "SELECT gender_type FROM buildings WHERE id = ?",
            [$room['building_id']]
        );

        if (!$building) {
            return false;
        }

        // Mixed buildings accept all
        if ($building['gender_type'] === self::BUILDING_MIXED) {
            return true;
        }

        // Single-gender must match
        return $building['gender_type'] === $student['gender'];
    }
}
