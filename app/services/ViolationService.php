<?php
/**
 * app/services/ViolationService.php
 * ─────────────────────────────────────────────────────────────
 *  Dịch vụ xử lý vi phạm (violation service)
 *
 *  Chức năng:
 *  • Ghi nhận vi phạm mới
 *  • Tự động tính điểm dựa loại vi phạm
 *  • Auto-flag contract nếu vượt ngưỡng
 *  • Quản lý trạng thái contract
 *  • Gửi thông báo cho sinh viên & admin
 *
 *  Tính điểm trừ:
 *  • Mỗi loại vi phạm có điểm mặc định
 *  • Admin có thể override điểm
 *  • Tính tổng điểm (chỉ active violations)
 *  • Nếu >= 10 điểm → Flag contract status = 'under_review'
 *
 *  Quản lý trạng thái Contract:
 *  • active: Bình thường
 *  • under_review: Chờ xem xét (do vi phạm)
 *  • suspended: Tạm ngừng (do vi phạm quá nặng)
 *  • terminated: Kết thúc (hợp đồng hết hạn hoặc đủ điều kiện)
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Models.php';

class ViolationService
{
    private Database $db;

    /**
     * Violation types with severity & default points
     */
    private const VIOLATION_CATALOG = [
        'room_cleanliness'  => ['severity' => 1, 'points' => 1, 'name' => 'Phòng không sạch sẽ'],
        'noise'             => ['severity' => 1, 'points' => 2, 'name' => 'Gây tiếng ồn'],
        'guests'            => ['severity' => 1, 'points' => 2, 'name' => 'Khách không đăng ký'],
        'curfew'            => ['severity' => 2, 'points' => 3, 'name' => 'Vi phạm giờ về'],
        'unauthorized_item' => ['severity' => 2, 'points' => 3, 'name' => 'Vật dụng cấm'],
        'alcohol'           => ['severity' => 3, 'points' => 5, 'name' => 'Uống rượu/bia'],
        'smoking'           => ['severity' => 2, 'points' => 3, 'name' => 'Hút thuốc'],
        'fighting'          => ['severity' => 3, 'points' => 7, 'name' => 'Đánh nhau/Cãi vã'],
        'damage'            => ['severity' => 3, 'points' => 8, 'name' => 'Phá hủy tài sản'],
        'theft'             => ['severity' => 3, 'points' => 10, 'name' => 'Trộm cắp'],
        'other'             => ['severity' => 1, 'points' => 1, 'name' => 'Khác'],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  MAIN FUNCTION: Record Violation
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Ghi nhận vi phạm và tự động tính điểm
     *
     * Steps:
     *   1. Validate input
     *   2. Calculate penalty points
     *   3. Insert into violation_records
     *   4. Evaluate student status
     *   5. Flag contract if needed
     *   6. Send notifications
     *   7. Write log
     *
     * @param int $studentId
     * @param string $violationType (key từ VIOLATION_CATALOG)
     * @param string $description
     * @param string $location
     * @param string $witnessedBy
     * @param string $evidence
     * @param ?int $overridePoints (admin override)
     * @param int $recordedBy (admin ID)
     *
     * @return array {
     *     "success": bool,
     *     "message": string,
     *     "data": { violation record } | null
     * }
     */
    public function recordViolation(
        int $studentId,
        string $violationType,
        string $description,
        string $location = '',
        string $witnessedBy = '',
        string $evidence = '',
        ?int $overridePoints = null,
        int $recordedBy = 0
    ): array {
        // ─── Step 1: Validate ──────────────────────────────────
        $student = $this->db->selectOne(
            "SELECT id, user_id FROM students WHERE id = ?",
            [$studentId]
        );

        if (!$student) {
            return $this->error('Sinh viên không tồn tại');
        }

        if (!isset(self::VIOLATION_CATALOG[$violationType])) {
            return $this->error('Loại vi phạm không hợp lệ');
        }

        // ─── Step 2: Calculate penalty points ──────────────────
        $penaltyPoints = $overridePoints !== null
            ? $overridePoints
            : self::VIOLATION_CATALOG[$violationType]['points'];

        $severity = self::VIOLATION_CATALOG[$violationType]['severity'];

        // ─── Step 3: Insert violation ──────────────────────────
        try {
            $violationId = $this->db->transaction(function (Database $db) use (
                $studentId,
                $violationType,
                $description,
                $location,
                $witnessedBy,
                $evidence,
                $penaltyPoints,
                $severity,
                $recordedBy
            ) {
                // Insert violation record
                $id = $db->insert('violation_records', [
                    'student_id'      => $studentId,
                    'violation_type'  => $violationType,
                    'description'     => $description,
                    'location'        => $location,
                    'witnessed_by'    => $witnessedBy,
                    'evidence'        => $evidence,
                    'penalty_points'  => $penaltyPoints,
                    'severity'        => $severity,
                    'status'          => 'active',
                    'recorded_by'     => $recordedBy,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);

                // Step 4: Evaluate student status
                $this->evaluateStudentStatus($studentId);

                // Step 6: Send notifications
                $this->notifyViolation($studentId, $penaltyPoints, $violationType);

                // Step 7: Write log
                error_log(sprintf(
                    '[VIOLATION] Student #%d recorded violation #%d (%s, %d pts)',
                    $studentId,
                    $id,
                    $violationType,
                    $penaltyPoints
                ));

                return $id;
            });

            // Fetch the created violation
            $violation = $this->db->selectOne(
                "SELECT * FROM violation_records WHERE id = ?",
                [$violationId]
            );

            return $this->success($violation, 'Ghi nhận vi phạm thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage());
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  AUTO-EVALUATION LOGIC
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Đánh giá trạng thái của sinh viên dựa trên tổng điểm vi phạm
     *
     * Logic:
     *   • Tính tổng điểm active violations
     *   • Nếu >= VIOLATION_THRESHOLD (10) → Flag contract under_review
     *   • Nếu >= 15 → Flag contract suspended
     *   • Nếu < 10 → Restore contract to active
     *
     * @param int $studentId
     */
    public function evaluateStudentStatus(int $studentId): void
    {
        // Get total active violation points
        $totalPoints = $this->db->selectValue(
            "SELECT COALESCE(SUM(penalty_points), 0)
             FROM violation_records
             WHERE student_id = ? AND status = 'active'",
            [$studentId]
        ) ?? 0;

        // Get student's active contract
        $contract = $this->db->selectOne(
            "SELECT id, status FROM contracts
             WHERE student_id = ? AND status IN ('active', 'under_review', 'suspended')",
            [$studentId]
        );

        if (!$contract) {
            // No active contract, nothing to do
            return;
        }

        // Determine new status
        $newStatus = $this->determineContractStatus((int)$totalPoints, $contract['status']);

        // Only update if status changed
        if ($newStatus !== $contract['status']) {
            $this->updateContractStatus(
                (int)$contract['id'],
                $contract['status'],
                $newStatus,
                (int)$totalPoints
            );
        }
    }

    /**
     * Xác định trạng thái hợp đồng dựa vào điểm vi phạm
     *
     * @return string 'active' | 'under_review' | 'suspended'
     */
    private function determineContractStatus(int $totalPoints, string $currentStatus): string
    {
        // Threshold definitions
        $WARN_THRESHOLD = VIOLATION_THRESHOLD;      // 10
        $SUSPEND_THRESHOLD = 15;                     // 15

        if ($totalPoints >= $SUSPEND_THRESHOLD) {
            return 'suspended';
        } elseif ($totalPoints >= $WARN_THRESHOLD) {
            return 'under_review';
        } else {
            // Below threshold: restore to active if was flagged
            return 'active';
        }
    }

    /**
     * Cập nhật trạng thái hợp đồng
     */
    private function updateContractStatus(
        int $contractId,
        string $oldStatus,
        string $newStatus,
        int $totalPoints
    ): void {
        $this->db->transaction(function (Database $db) use (
            $contractId,
            $oldStatus,
            $newStatus,
            $totalPoints
        ) {
            // Update contract status
            $db->update(
                'contracts',
                [
                    'status'      => $newStatus,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ],
                'id = ?',
                [$contractId]
            );

            // Get student for notifications
            $contract = $db->selectOne(
                "SELECT student_id FROM contracts WHERE id = ?",
                [$contractId]
            );

            $student = $db->selectOne(
                "SELECT s.user_id, s.full_name FROM students s WHERE id = ?",
                [$contract['student_id']]
            );

            if (!$student) return;

            // Send notifications based on new status
            if ($newStatus === 'suspended') {
                $db->insert('notifications', [
                    'user_id' => $student['user_id'],
                    'title'   => 'CẢNH BÁO: Hợp đồng bị tạm ngừng',
                    'message' => "Hợp đồng phòng của bạn bị tạm ngừng do tích lũy {$totalPoints} điểm vi phạm.",
                    'type'    => 'violation',
                ]);
            } elseif ($newStatus === 'under_review') {
                $db->insert('notifications', [
                    'user_id' => $student['user_id'],
                    'title'   => 'CẢNH CÁO: Hợp đồng đang được xem xét',
                    'message' => "Hợp đồng phòng của bạn đang được xem xét do {$totalPoints} điểm vi phạm.",
                    'type'    => 'violation',
                ]);
            } elseif ($oldStatus !== 'active' && $newStatus === 'active') {
                // Restored
                $db->insert('notifications', [
                    'user_id' => $student['user_id'],
                    'title'   => 'Hợp đồng được khôi phục',
                    'message' => "Hợp đồng phòng của bạn đã được khôi phục. Tổng điểm vi phạm: {$totalPoints}",
                    'type'    => 'violation',
                ]);
            }

            // Log the status change
            error_log(sprintf(
                '[CONTRACT_STATUS] Contract #%d: %s → %s (violations: %d pts)',
                $contractId,
                $oldStatus,
                $newStatus,
                $totalPoints
            ));
        });
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  NOTIFICATION SYSTEM
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Gửi thông báo cho sinh viên khi bị ghi nhận vi phạm
     */
    private function notifyViolation(
        int $studentId,
        int $penaltyPoints,
        string $violationType
    ): void {
        $student = $this->db->selectOne(
            "SELECT s.user_id, s.full_name FROM students s WHERE id = ?",
            [$studentId]
        );

        if (!$student) return;

        $violationName = self::VIOLATION_CATALOG[$violationType]['name'] ?? 'Khác';

        // Get current total points
        $totalPoints = $this->db->selectValue(
            "SELECT COALESCE(SUM(penalty_points), 0)
             FROM violation_records
             WHERE student_id = ? AND status = 'active'",
            [$studentId]
        ) ?? 0;

        // Compose message based on severity
        if ($totalPoints >= VIOLATION_THRESHOLD) {
            $message = "Bạn vừa bị ghi nhận vi phạm: {$violationName} ({$penaltyPoints} điểm). "
                     . "CẢNH CÁO: Tổng điểm vi phạm của bạn đã đạt " . VIOLATION_THRESHOLD . " điểm. "
                     . "Hợp đồng của bạn sẽ được xem xét.";
        } else {
            $message = "Bạn vừa bị ghi nhận vi phạm: {$violationName} ({$penaltyPoints} điểm). "
                     . "Tổng điểm vi phạm hiện tại: {$totalPoints}/" . VIOLATION_THRESHOLD . ".";
        }

        $this->db->insert('notifications', [
            'user_id' => $student['user_id'],
            'title'   => 'Ghi nhận vi phạm',
            'message' => $message,
            'type'    => 'violation',
        ]);
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  UTILITY METHODS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Lấy tổng điểm vi phạm của sinh viên
     */
    public function getTotalPoints(int $studentId, string $status = 'active'): int
    {
        return $this->db->selectValue(
            "SELECT COALESCE(SUM(penalty_points), 0)
             FROM violation_records
             WHERE student_id = ? AND status = ?",
            [$studentId, $status]
        ) ?? 0;
    }

    /**
     * Kiểm tra sinh viên có bị cấm không (points >= threshold)
     */
    public function isStudentBanned(int $studentId): bool
    {
        return $this->getTotalPoints($studentId, 'active') >= VIOLATION_THRESHOLD;
    }

    /**
     * Lấy violation catalog
     */
    public static function getCatalog(): array
    {
        return self::VIOLATION_CATALOG;
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  RESPONSE HELPERS
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
}
