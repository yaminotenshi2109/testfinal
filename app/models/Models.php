<?php
/**
 * app/models/UserModel.php
 * ─────────────────────────────────────────────────────────────
 *  Thành viên 1 phụ trách
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseModel.php';

class UserModel extends BaseModel
{
    protected string $table    = 'users';
    protected array  $fillable = ['username', 'email', 'password_hash', 'role', 'status'];

    // ── Tìm theo email ────────────────────────────────────────
    public function findByEmail(string $email): ?array
    {
        return $this->whereFirst('email = ?', [$email]);
    }

    // ── Tìm theo username ─────────────────────────────────────
    public function findByUsername(string $username): ?array
    {
        return $this->whereFirst('username = ?', [$username]);
    }

    // ── Đăng ký tài khoản mới ────────────────────────────────
    public function register(array $data): int
    {
        // Không bao giờ lưu plain-text password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        unset($data['password'], $data['password_confirm']);

        return $this->create($data);
    }

    // ── Xác thực đăng nhập ───────────────────────────────────
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if (!$user || $user['status'] !== 'active') {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Nâng cấp hash nếu cần (PHP 8+)
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->update($user['id'], ['password_hash' => $newHash]);
        }

        unset($user['password_hash']); // không trả password hash ra ngoài
        return $user;
    }

    // ── Email/username đã tồn tại chưa ───────────────────────
    public function emailExists(string $email, int $excludeId = 0): bool
    {
        if ($excludeId > 0) {
            return $this->exists('email = ? AND id != ?', [$email, $excludeId]);
        }
        return $this->exists('email = ?', [$email]);
    }

    public function usernameExists(string $username, int $excludeId = 0): bool
    {
        if ($excludeId > 0) {
            return $this->exists('username = ? AND id != ?', [$username, $excludeId]);
        }
        return $this->exists('username = ?', [$username]);
    }

    // ── Lấy danh sách kèm thông tin sinh viên (JOIN) ─────────
    public function allWithStudentProfile(): array
    {
        return $this->db->select("
            SELECT u.id, u.username, u.email, u.role, u.status, u.created_at,
                   s.student_code, s.full_name, s.gender, s.faculty, s.priority_level
            FROM users u
            LEFT JOIN students s ON s.user_id = u.id
            ORDER BY u.created_at DESC
        ");
    }

    // ── Đổi mật khẩu ─────────────────────────────────────────
    public function changePassword(int $userId, string $newPassword): bool
    {
        $hash    = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $updated = $this->update($userId, ['password_hash' => $hash]);
        return $updated > 0;
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * app/models/StudentModel.php
 * Thành viên 1 phụ trách
 */
class StudentModel extends BaseModel
{
    protected string $table    = 'students';
    protected array  $fillable = [
        'user_id','student_code','full_name','gender','dob',
        'faculty','program','priority_level','phone','hometown','id_card',
    ];

    public function findByUserId(int $userId): ?array
    {
        return $this->whereFirst('user_id = ?', [$userId]);
    }

    public function findByStudentCode(string $code): ?array
    {
        return $this->whereFirst('student_code = ?', [$code]);
    }

    /** Tìm sinh viên kèm thông tin tài khoản */
    public function findWithUser(int $studentId): ?array
    {
        return $this->db->selectOne("
            SELECT s.*, u.username, u.email, u.status AS account_status
            FROM students s
            JOIN users u ON u.id = s.user_id
            WHERE s.id = ?
        ", [$studentId]);
    }

    /** Sinh viên có thể đăng ký phòng học kỳ này chưa */
    public function hasActiveRegistration(int $studentId, string $semester, int $year): bool
    {
        return $this->db->exists(
            'room_registrations',
            "student_id = ? AND semester = ? AND academic_year = ?
             AND status IN ('pending','approved')",
            [$studentId, $semester, $year]
        );
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * app/models/BuildingModel.php
 * Thành viên 1 phụ trách
 */
class BuildingModel extends BaseModel
{
    protected string $table    = 'buildings';
    protected array  $fillable = [
        'name','total_floors','gender_type',
        'manager_name','manager_phone','address','status',
    ];

    public function active(): array
    {
        return $this->where("status = 'active'");
    }

    /** Thống kê số phòng theo tòa */
    public function withRoomStats(): array
    {
        return $this->db->select("
            SELECT b.*,
                   COUNT(r.id)                                       AS total_rooms,
                   SUM(r.capacity)                                   AS total_beds,
                   SUM(r.current_occupants)                         AS occupied_beds,
                   SUM(r.capacity) - SUM(r.current_occupants)       AS available_beds
            FROM buildings b
            LEFT JOIN rooms r ON r.building_id = b.id AND r.status != 'inactive'
            GROUP BY b.id
            ORDER BY b.name
        ");
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * app/models/RoomModel.php
 * Thành viên 2 phụ trách
 */
class RoomModel extends BaseModel
{
    protected string $table    = 'rooms';
    protected array  $fillable = [
        'building_id','room_number','floor','room_type',
        'capacity','price_per_month','has_ac','status',
    ];

    /** Phòng còn chỗ theo giới tính tòa nhà */
    public function availableFor(string $gender): array
    {
        // gender_type: 'male' | 'female' | 'mixed'
        return $this->db->select("
            SELECT r.*, b.name AS building_name, b.gender_type
            FROM rooms r
            JOIN buildings b ON b.id = r.building_id
            WHERE r.status = 'available'
              AND r.current_occupants < r.capacity
              AND (b.gender_type = ? OR b.gender_type = 'mixed')
              AND b.status = 'active'
            ORDER BY b.name, r.room_number
        ", [$gender]);
    }

    /**
     * Room Allocation Logic (Thành viên 2 — nghiệp vụ quan trọng)
     *
     * Tự động chọn phòng phù hợp nhất cho sinh viên:
     *   1. Đúng giới tính tòa
     *   2. Còn giường trống
     *   3. Ưu tiên phòng gần đầy hơn (giảm phòng lãng phí)
     *   4. Ưu tiên giá thấp hơn nếu không có priority
     */
    public function allocate(string $gender, int $priorityLevel = 0): ?array
    {
        $preferFuller = $priorityLevel > 0 ? 'DESC' : 'ASC'; // chính sách → phòng đặc hơn

        return $this->db->selectOne("
            SELECT r.*, b.name AS building_name
            FROM rooms r
            JOIN buildings b ON b.id = r.building_id
            WHERE r.status = 'available'
              AND r.current_occupants < r.capacity
              AND (b.gender_type = ? OR b.gender_type = 'mixed')
              AND b.status = 'active'
            ORDER BY
              r.current_occupants {$preferFuller},
              r.price_per_month ASC
            LIMIT 1
        ", [$gender]);
    }

    /** Cập nhật trạng thái phòng tự động */
    public function refreshStatus(int $roomId): void
    {
        $room = $this->find($roomId);
        if (!$room) return;

        if ($room['current_occupants'] >= $room['capacity']) {
            $newStatus = 'full';
        } elseif ($room['status'] === 'full') {
            $newStatus = 'available';
        } else {
            return; // không thay đổi
        }

        $this->db->update('rooms', ['status' => $newStatus], 'id = ?', [$roomId]);
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * app/models/ContractModel.php
 * Thành viên 2 phụ trách
 */
class ContractModel extends BaseModel
{
    protected string $table    = 'contracts';
    protected array  $fillable = [
        'registration_id','student_id','room_id',
        'start_date','end_date','monthly_fee','status',
        'terminated_at','terminated_reason',
    ];

    /** Tạo hợp đồng trong transaction (an toàn) */
    public function createContract(array $data): int
    {
        return $this->db->transaction(function (Database $db) use ($data) {
            // 1. Tạo hợp đồng
            $contractId = $db->insert('contracts', $this->filterFillable($data));

            // 2. Cập nhật trạng thái đăng ký → approved
            $db->update(
                'room_registrations',
                ['status' => 'approved', 'reviewed_at' => date('Y-m-d H:i:s')],
                'id = ?',
                [$data['registration_id']]
            );

            // 3. Tăng current_occupants (backup nếu trigger không kích hoạt)
            $db->query(
                "UPDATE rooms SET current_occupants = current_occupants + 1 WHERE id = ?",
                [$data['room_id']]
            );

            // 4. Gửi thông báo cho sinh viên
            $student = $db->selectOne(
                "SELECT s.user_id, s.full_name FROM students s WHERE s.id = ?",
                [$data['student_id']]
            );
            if ($student) {
                $db->insert('notifications', [
                    'user_id' => $student['user_id'],
                    'title'   => 'Hợp đồng KTX đã được tạo',
                    'message' => "Hợp đồng thuê phòng từ {$data['start_date']} đến {$data['end_date']} đã được ký.",
                    'type'    => 'contract',
                ]);
            }

            return $contractId;
        });
    }

    /** Chấm dứt hợp đồng sớm */
    public function terminate(int $contractId, string $reason): bool
    {
        return $this->db->transaction(function (Database $db) use ($contractId, $reason) {
            $contract = $db->find('contracts', $contractId);
            if (!$contract || $contract['status'] !== 'active') {
                throw new \RuntimeException('Hợp đồng không hợp lệ để chấm dứt.');
            }

            // Cập nhật hợp đồng
            $db->update('contracts', [
                'status'            => 'terminated',
                'terminated_at'     => date('Y-m-d H:i:s'),
                'terminated_reason' => $reason,
            ], 'id = ?', [$contractId]);

            // Giảm occupants
            $db->query(
                "UPDATE rooms
                 SET current_occupants = GREATEST(0, current_occupants - 1)
                 WHERE id = ?",
                [$contract['room_id']]
            );

            return true;
        });
    }

    /** Hợp đồng active của sinh viên */
    public function activeByStudent(int $studentId): ?array
    {
        return $this->db->selectOne("
            SELECT c.*, r.room_number, b.name AS building_name,
                   r.price_per_month, r.room_type
            FROM contracts c
            JOIN rooms r ON r.id = c.room_id
            JOIN buildings b ON b.id = r.building_id
            WHERE c.student_id = ? AND c.status = 'active'
            LIMIT 1
        ", [$studentId]);
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * app/models/InvoiceModel.php
 * Thành viên 3 phụ trách
 */
class InvoiceModel extends BaseModel
{
    protected string $table    = 'invoices';
    protected array  $fillable = [
        'contract_id','month','year','base_rent',
        'electricity_fee','water_fee','ac_fee','other_fee',
        'total_amount','status','due_date','paid_at','payment_method',
    ];

    /**
     * Billing Engine — tính và tạo hóa đơn từ utility_readings
     *
     * @param int $contractId
     * @param int $month
     * @param int $year
     * @return int  invoice ID
     */
    public function generateFromReadings(int $contractId, int $month, int $year): int
    {
        $contract = $this->db->find('contracts', $contractId);
        if (!$contract) {
            throw new \RuntimeException("Không tìm thấy hợp đồng #{$contractId}");
        }

        // Đã có hóa đơn tháng này chưa?
        if ($this->db->exists(
            'invoices',
            'contract_id = ? AND month = ? AND year = ?',
            [$contractId, $month, $year]
        )) {
            throw new \RuntimeException("Hóa đơn tháng {$month}/{$year} đã tồn tại.");
        }

        // Lấy chỉ số điện nước
        $reading = $this->db->selectOne(
            "SELECT * FROM utility_readings WHERE room_id = ? AND month = ? AND year = ?",
            [$contract['room_id'], $month, $year]
        );

        $elecRate  = $reading['elec_rate']  ?? DEFAULT_ELEC_RATE;
        $waterRate = $reading['water_rate'] ?? DEFAULT_WATER_RATE;

        // Tính phí điện nước
        $elecUsage   = $reading ? ($reading['elec_curr'] - $reading['elec_prev']) : 0;
        $waterUsage  = $reading ? ($reading['water_curr'] - $reading['water_prev']) : 0;
        $elecFee     = round($elecUsage  * $elecRate,  2);
        $waterFee    = round($waterUsage * $waterRate, 2);

        // Phí điều hòa
        $room   = $this->db->find('rooms', $contract['room_id']);
        $acFee  = ($room && $room['has_ac']) ? AC_FEE_MONTHLY : 0;

        $baseRent = (float)$contract['monthly_fee'];
        $total    = $baseRent + $elecFee + $waterFee + $acFee;
        $dueDate  = date('Y-m-d', strtotime("last day of {$year}-{$month}"));

        return $this->db->transaction(function (Database $db) use (
            $contractId, $month, $year,
            $baseRent, $elecFee, $waterFee, $acFee, $total, $dueDate,
            $contract
        ) {
            $invoiceId = $db->insert('invoices', [
                'contract_id'     => $contractId,
                'month'           => $month,
                'year'            => $year,
                'base_rent'       => $baseRent,
                'electricity_fee' => $elecFee,
                'water_fee'       => $waterFee,
                'ac_fee'          => $acFee,
                'other_fee'       => 0,
                'total_amount'    => $total,
                'status'          => 'unpaid',
                'due_date'        => $dueDate,
            ]);

            // Thông báo sinh viên
            $student = $db->selectOne(
                "SELECT s.user_id FROM students s WHERE s.id = ?",
                [$contract['student_id']]
            );
            if ($student) {
                $db->insert('notifications', [
                    'user_id' => $student['user_id'],
                    'title'   => "Hóa đơn tháng {$month}/{$year}",
                    'message' => "Hóa đơn tháng {$month}/{$year} đã được tạo. Tổng: "
                                 . number_format($total, 0, ',', '.') . " VND. Hạn nộp: {$dueDate}.",
                    'type'    => 'invoice',
                ]);
            }

            return $invoiceId;
        });
    }

    /** Đánh dấu đã thanh toán */
    public function markPaid(int $invoiceId, string $paymentMethod = 'cash'): bool
    {
        $updated = $this->db->update('invoices', [
            'status'         => 'paid',
            'paid_at'        => date('Y-m-d H:i:s'),
            'payment_method' => $paymentMethod,
        ], 'id = ? AND status = ?', [$invoiceId, 'unpaid']);

        return $updated > 0;
    }

    /** Hóa đơn của sinh viên */
    public function byStudent(int $studentId): array
    {
        return $this->db->select("
            SELECT i.*, c.student_id, r.room_number, b.name AS building_name
            FROM invoices i
            JOIN contracts c ON c.id = i.contract_id
            JOIN rooms r ON r.id = c.room_id
            JOIN buildings b ON b.id = r.building_id
            WHERE c.student_id = ?
            ORDER BY i.year DESC, i.month DESC
        ", [$studentId]);
    }

    /** Tổng doanh thu theo năm (cho admin dashboard) */
    public function revenueByMonth(int $year): array
    {
        return $this->db->select("
            SELECT month,
                   SUM(total_amount) AS revenue,
                   COUNT(*)          AS invoice_count,
                   SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) AS collected
            FROM invoices
            WHERE year = ? AND status != 'cancelled'
            GROUP BY month
            ORDER BY month
        ", [$year]);
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * app/models/ViolationModel.php
 * Thành viên 3 phụ trách
 */
class ViolationModel extends BaseModel
{
    protected string $table    = 'violation_records';
    protected array  $fillable = [
        'student_id','contract_id','violation_type',
        'description','penalty_points','recorded_by','status','appeal_note',
    ];

    /** Tổng điểm vi phạm của sinh viên (chỉ tính active) */
    public function totalPoints(int $studentId): int
    {
        return (int)$this->db->selectValue(
            "SELECT COALESCE(SUM(penalty_points), 0)
             FROM violation_records
             WHERE student_id = ? AND status = 'active'",
            [$studentId]
        );
    }

    /** Thêm vi phạm và kiểm tra ngưỡng */
    public function recordAndCheck(array $data): array
    {
        return $this->db->transaction(function (Database $db) use ($data) {
            $violationId = $db->insert('violation_records', $this->filterFillable($data));
            $totalPoints = $this->totalPoints($data['student_id']);

            $flagged = false;
            if ($totalPoints >= VIOLATION_THRESHOLD) {
                // Cập nhật hợp đồng → under_review
                $db->update(
                    'contracts',
                    ['status' => 'under_review'],
                    'student_id = ? AND status = ?',
                    [$data['student_id'], 'active']
                );
                $flagged = true;
            }

            return [
                'violation_id' => $violationId,
                'total_points' => $totalPoints,
                'flagged'      => $flagged,
            ];
        });
    }

    /** Lịch sử vi phạm kèm thông tin sinh viên */
    public function withStudentInfo(): array
    {
        return $this->db->select("
            SELECT v.*, s.full_name, s.student_code,
                   u.username AS recorded_by_name
            FROM violation_records v
            JOIN students s ON s.id = v.student_id
            JOIN users u    ON u.id = v.recorded_by
            ORDER BY v.recorded_at DESC
        ");
    }
}
