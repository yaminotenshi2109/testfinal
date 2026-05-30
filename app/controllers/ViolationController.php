<?php
/**
 * app/controllers/ViolationController.php
 * ─────────────────────────────────────────────────────────────
 *  Quản lý vi phạm KTX và tính điểm trừ tự động
 *
 *  Chức năng:
 *  • Admin ghi nhận vi phạm (AJAX CRUD)
 *  • Tự động tính điểm dựa vào loại vi phạm
 *  • Auto-flag contract nếu vượt ngưỡng
 *  • Appeal/Dismiss workflow
 *  • Student view violations
 *  • Statistics & reports
 *
 *  Điểm vi phạm (Penalty Points):
 *  • Cấp độ 1 (Nhẹ): 1-2 điểm (tạp vụ, không gọn, ...)
 *  • Cấp độ 2 (Trung bình): 3-5 điểm (sinh hoạt, gây mất trật tự)
 *  • Cấp độ 3 (Nặng): 5-10 điểm (phá hủy, uống rượu, ...)
 *  • Ngưỡng cảnh báo: 10 điểm (VIOLATION_THRESHOLD)
 *
 *  Thành viên 3 phụ trách (Violation module)
 *  Điểm "Xuất sắc": Complex logic + Auto-flag + Appeal system
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Models.php';
require_once __DIR__ . '/../services/ViolationService.php';

class ViolationController extends BaseController
{
    private ViolationService $violationService;
    private StudentModel $studentModel;

    /**
     * Violation severity levels & points
     */
    private const SEVERITY_LIGHT = 1;      // 1-2 điểm
    private const SEVERITY_MEDIUM = 2;     // 3-5 điểm
    private const SEVERITY_HEAVY = 3;      // 5-10 điểm

    /**
     * Violation categories with default points
     */
    private const VIOLATION_TYPES = [
        'room_cleanliness'  => ['name' => 'Phòng không sạch sẽ', 'points' => 1, 'severity' => self::SEVERITY_LIGHT],
        'noise'             => ['name' => 'Gây tiếng ồn', 'points' => 2, 'severity' => self::SEVERITY_LIGHT],
        'guests'            => ['name' => 'Khách không đăng ký', 'points' => 2, 'severity' => self::SEVERITY_LIGHT],
        'curfew'            => ['name' => 'Vi phạm giờ về', 'points' => 3, 'severity' => self::SEVERITY_MEDIUM],
        'unauthorized_item' => ['name' => 'Vật dụng cấm', 'points' => 3, 'severity' => self::SEVERITY_MEDIUM],
        'alcohol'           => ['name' => 'Uống rượu/bia', 'points' => 5, 'severity' => self::SEVERITY_HEAVY],
        'smoking'           => ['name' => 'Hút thuốc', 'points' => 3, 'severity' => self::SEVERITY_MEDIUM],
        'fighting'          => ['name' => 'Đánh nhau/Cãi vã', 'points' => 7, 'severity' => self::SEVERITY_HEAVY],
        'damage'            => ['name' => 'Phá hủy tài sản', 'points' => 8, 'severity' => self::SEVERITY_HEAVY],
        'theft'             => ['name' => 'Trộm cắp', 'points' => 10, 'severity' => self::SEVERITY_HEAVY],
        'other'             => ['name' => 'Khác', 'points' => 1, 'severity' => self::SEVERITY_LIGHT],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->violationService = new ViolationService();
        $this->studentModel = new StudentModel();
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  ADMIN ENDPOINTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /admin/violations
     * Danh sách vi phạm
     */
    public function index(array $params = []): void
    {
        $this->requireAdmin();

        [$page, $perPage] = $this->paginationParams();

        // Filters
        $status = $this->request('status', '');
        $severity = $this->request('severity', '');
        $search = $this->request('q', '');

        $where = '1';
        $args = [];

        if ($status) {
            $where .= ' AND v.status = ?';
            $args[] = $status;
        }

        if ($severity) {
            $where .= ' AND v.severity = ?';
            $args[] = $severity;
        }

        if ($search) {
            $where .= ' AND (s.full_name LIKE ? OR v.violation_type LIKE ?)';
            $searchParam = "%{$search}%";
            $args[] = $searchParam;
            $args[] = $searchParam;
        }

        $result = $this->db->paginate(
            "SELECT v.*, s.full_name, s.student_code, u.username,
                    c.id AS contract_id
             FROM violation_records v
             JOIN students s ON s.id = v.student_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN contracts c ON c.student_id = s.id AND c.status = 'active'
             WHERE {$where}
             ORDER BY v.created_at DESC",
            $args,
            $page,
            $perPage
        );

        $this->view('admin/violations/index', [
            'title'       => 'Quản lý vi phạm KTX',
            'violations'  => $result['data'],
            'pagination'  => $result,
            'status'      => $status,
            'severity'    => $severity,
            'search'      => $search,
            'statuses'    => ['active' => 'Đang xử lý', 'appealed' => 'Khiếu nại', 'dismissed' => 'Hủy bỏ'],
            'severities'  => [self::SEVERITY_LIGHT => 'Nhẹ', self::SEVERITY_MEDIUM => 'Trung bình', self::SEVERITY_HEAVY => 'Nặng'],
            'types'       => self::VIOLATION_TYPES,
        ]);
    }

    /**
     * GET /admin/violations/:id
     * Chi tiết vi phạm
     */
    public function show(array $params = []): void
    {
        $this->requireAdmin();

        $id = (int)$params['id'];

        $violation = $this->db->selectOne(
            "SELECT v.*, s.full_name, s.student_code, u.username
             FROM violation_records v
             JOIN students s ON s.id = v.student_id
             JOIN users u ON u.id = s.user_id
             WHERE v.id = ?",
            [$id]
        );

        if (!$violation) {
            $this->abort(404, 'Vi phạm không tồn tại');
        }

        // Get student's total points
        $totalPoints = $this->db->selectValue(
            "SELECT COALESCE(SUM(penalty_points), 0)
             FROM violation_records
             WHERE student_id = ? AND status = 'active'",
            [$violation['student_id']]
        );

        // Get student's contract
        $contract = $this->db->selectOne(
            "SELECT id, status FROM contracts
             WHERE student_id = ? AND status IN ('active', 'under_review')",
            [$violation['student_id']]
        );

        $this->view('admin/violations/show', [
            'title'       => 'Chi tiết vi phạm',
            'violation'   => $violation,
            'totalPoints' => $totalPoints,
            'contract'    => $contract,
            'types'       => self::VIOLATION_TYPES,
        ]);
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  AJAX API ENDPOINTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * POST /api/violations
     * Ghi nhận vi phạm mới (Admin)
     *
     * Request:
     *   {
     *     "student_id": 5,
     *     "violation_type": "alcohol",     // key từ VIOLATION_TYPES
     *     "description": "Found drinking beer in room",
     *     "location": "Room 202",
     *     "witnessed_by": "Dormitory staff",
     *     "evidence": "Photo",
     *     "override_points": null          // Admin có thể override điểm
     *   }
     */
    public function store(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $data = $this->only([
            'student_id',
            'violation_type',
            'description',
            'location',
            'witnessed_by',
            'evidence',
            'override_points',
        ]);

        // Validation
        $errors = [];

        $student = $this->studentModel->find((int)$data['student_id']);
        if (!$student) {
            $errors['student_id'] = ['Sinh viên không tồn tại'];
        }

        if (!isset(self::VIOLATION_TYPES[$data['violation_type']])) {
            $errors['violation_type'] = ['Loại vi phạm không hợp lệ'];
        }

        if (empty($data['description']) || strlen($data['description']) < 5) {
            $errors['description'] = ['Mô tả phải ít nhất 5 ký tự'];
        }

        if (!empty($errors)) {
            $this->jsonError('Dữ liệu không hợp lệ', 422, $errors);
        }

        try {
            $result = $this->violationService->recordViolation(
                (int)$data['student_id'],
                $data['violation_type'],
                $data['description'],
                $data['location'] ?? '',
                $data['witnessed_by'] ?? '',
                $data['evidence'] ?? '',
                !empty($data['override_points']) ? (int)$data['override_points'] : null,
                (int)$this->auth('id')  // recorded_by admin ID
            );

            if (!$result['success']) {
                $this->jsonError($result['message'], 422);
            }

            $this->jsonOk(
                $result['data'],
                'Ghi nhận vi phạm thành công',
                201
            );
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/violations/:id
     * Cập nhật vi phạm
     */
    public function update(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];

        $violation = $this->db->selectOne(
            "SELECT * FROM violation_records WHERE id = ?",
            [$id]
        );

        if (!$violation) {
            $this->jsonError('Vi phạm không tồn tại', 404);
        }

        $data = $this->only([
            'description',
            'location',
            'witnessed_by',
            'evidence',
            'override_points',
        ]);

        // Validation
        if (!empty($data['description']) && strlen($data['description']) < 5) {
            $this->jsonError('Mô tả phải ít nhất 5 ký tự', 422, [
                'description' => ['Phải ít nhất 5 ký tự'],
            ]);
        }

        try {
            $this->db->update(
                'violation_records',
                array_filter([
                    'description' => $data['description'] ?? null,
                    'location' => $data['location'] ?? null,
                    'witnessed_by' => $data['witnessed_by'] ?? null,
                    'evidence' => $data['evidence'] ?? null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], fn($v) => $v !== null),
                'id = ?',
                [$id]
            );

            // If override_points changed, recalculate
            if (!empty($data['override_points'])) {
                $newPoints = (int)$data['override_points'];
                $oldPoints = $violation['penalty_points'];

                $this->db->update(
                    'violation_records',
                    ['penalty_points' => $newPoints],
                    'id = ?',
                    [$id]
                );

                // Trigger re-evaluation
                $this->violationService->evaluateStudentStatus($violation['student_id']);
            }

            $updated = $this->db->selectOne(
                "SELECT * FROM violation_records WHERE id = ?",
                [$id]
            );

            $this->jsonOk($updated, 'Cập nhật vi phạm thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/violations/:id
     * Xóa vi phạm (soft delete)
     */
    public function destroy(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];

        $violation = $this->db->selectOne(
            "SELECT * FROM violation_records WHERE id = ?",
            [$id]
        );

        if (!$violation) {
            $this->jsonError('Vi phạm không tồn tại', 404);
        }

        try {
            // Soft delete (set status = 'dismissed')
            $this->db->update(
                'violation_records',
                [
                    'status'        => 'dismissed',
                    'dismissed_at'  => date('Y-m-d H:i:s'),
                    'dismissed_by'  => $this->auth('id'),
                ],
                'id = ?',
                [$id]
            );

            // Re-evaluate student status
            $this->violationService->evaluateStudentStatus($violation['student_id']);

            $this->jsonOk(null, 'Hủy bỏ vi phạm thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  APPEAL ENDPOINTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * POST /api/violations/:id/appeal
     * Sinh viên khiếu nại vi phạm
     *
     * Request:
     *   {
     *     "reason": "I wasn't in the room at that time"
     *   }
     */
    public function appeal(array $params = []): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $id = (int)$params['id'];
        $reason = $this->request('reason', '');

        if (!$reason || strlen($reason) < 10) {
            $this->jsonError(
                'Lý do khiếu nại phải ít nhất 10 ký tự',
                422,
                ['reason' => ['Ít nhất 10 ký tự']]
            );
        }

        $violation = $this->db->selectOne(
            "SELECT v.*, s.user_id
             FROM violation_records v
             JOIN students s ON s.id = v.student_id
             WHERE v.id = ?",
            [$id]
        );

        if (!$violation) {
            $this->jsonError('Vi phạm không tồn tại', 404);
        }

        // Only student can appeal their own violation
        if ($violation['user_id'] !== $this->auth('id')) {
            $this->jsonError('Bạn không có quyền khiếu nại', 403);
        }

        if ($violation['status'] === 'appealed') {
            $this->jsonError('Vi phạm đã được khiếu nại rồi', 409);
        }

        try {
            $this->db->update(
                'violation_records',
                [
                    'status'        => 'appealed',
                    'appeal_reason' => $reason,
                    'appealed_at'   => date('Y-m-d H:i:s'),
                ],
                'id = ?',
                [$id]
            );

            // Notify admin
            $this->db->insert('notifications', [
                'user_id' => null,  // Send to all admins later
                'title'   => 'Sinh viên khiếu nại vi phạm',
                'message' => "Sinh viên " . $violation['student_id'] . " khiếu nại vi phạm #" . $id,
                'type'    => 'violation',
            ]);

            $this->jsonOk(null, 'Khiếu nại vi phạm thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/violations/:id/dismiss-appeal
     * Admin từ chối khiếu nại
     *
     * Request:
     *   {
     *     "reason": "Evidence is clear"
     *   }
     */
    public function dismissAppeal(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];
        $reason = $this->request('reason', '');

        $violation = $this->db->selectOne(
            "SELECT * FROM violation_records WHERE id = ?",
            [$id]
        );

        if (!$violation) {
            $this->jsonError('Vi phạm không tồn tại', 404);
        }

        if ($violation['status'] !== 'appealed') {
            $this->jsonError('Vi phạm này không có khiếu nại đang xem xét', 409);
        }

        try {
            $this->db->update(
                'violation_records',
                [
                    'status'                 => 'active',
                    'appeal_dismiss_reason'  => $reason,
                    'appeal_dismissed_at'    => date('Y-m-d H:i:s'),
                    'appeal_dismissed_by'    => $this->auth('id'),
                ],
                'id = ?',
                [$id]
            );

            // Notify student
            $student = $this->db->selectOne(
                "SELECT s.user_id FROM students s WHERE id = ?",
                [$violation['student_id']]
            );

            if ($student) {
                $this->db->insert('notifications', [
                    'user_id' => $student['user_id'],
                    'title'   => 'Khiếu nại vi phạm bị từ chối',
                    'message' => "Lý do: {$reason}",
                    'type'    => 'violation',
                ]);
            }

            $this->jsonOk(null, 'Từ chối khiếu nại thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/violations/:id/accept-appeal
     * Admin chấp nhận khiếu nại (hủy vi phạm)
     */
    public function acceptAppeal(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];

        $violation = $this->db->selectOne(
            "SELECT * FROM violation_records WHERE id = ?",
            [$id]
        );

        if (!$violation) {
            $this->jsonError('Vi phạm không tồn tại', 404);
        }

        if ($violation['status'] !== 'appealed') {
            $this->jsonError('Vi phạm này không có khiếu nại đang xem xét', 409);
        }

        try {
            $this->db->transaction(function (Database $db) use ($id, $violation) {
                // Mark as dismissed (appeal accepted)
                $db->update(
                    'violation_records',
                    [
                        'status'              => 'dismissed',
                        'appeal_accepted_at'  => date('Y-m-d H:i:s'),
                        'appeal_accepted_by'  => $this->auth('id'),
                    ],
                    'id = ?',
                    [$id]
                );

                // Notify student
                $student = $db->selectOne(
                    "SELECT s.user_id FROM students s WHERE id = ?",
                    [$violation['student_id']]
                );

                if ($student) {
                    $db->insert('notifications', [
                        'user_id' => $student['user_id'],
                        'title'   => 'Khiếu nại vi phạm được chấp nhận',
                        'message' => 'Vi phạm của bạn đã được hủy bỏ.',
                        'type'    => 'violation',
                    ]);
                }

                // Re-evaluate student status (points may have decreased)
                $this->violationService->evaluateStudentStatus($violation['student_id']);
            });

            $this->jsonOk(null, 'Chấp nhận khiếu nại thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  STUDENT ENDPOINTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /api/student/violations
     * Sinh viên xem các vi phạm của mình
     */
    public function studentViolations(array $params = []): void
    {
        $this->requireAuth();

        $userId = $this->auth('id');
        $student = $this->db->selectOne(
            "SELECT id FROM students WHERE user_id = ?",
            [$userId]
        );

        if (!$student) {
            $this->jsonError('Không tìm thấy hồ sơ sinh viên', 404);
        }

        $violations = $this->db->select(
            "SELECT * FROM violation_records
             WHERE student_id = ?
             ORDER BY created_at DESC",
            [$student['id']]
        );

        // Calculate totals
        $totalPoints = array_sum(array_column($violations, 'penalty_points'));
        $activePoints = array_sum(
            array_map(
                fn($v) => $v['status'] === 'active' ? $v['penalty_points'] : 0,
                $violations
            )
        );

        $this->jsonOk([
            'violations'   => $violations,
            'total_points' => $totalPoints,
            'active_points' => $activePoints,
            'threshold'    => VIOLATION_THRESHOLD,
            'is_banned'    => $activePoints >= VIOLATION_THRESHOLD,
        ], 'Lấy danh sách vi phạm');
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  STATISTICS & REPORTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /api/violations/stats
     * Thống kê vi phạm
     */
    public function stats(array $params = []): void
    {
        $this->requireAdmin();

        $stats = [
            // Count by status
            'by_status' => $this->db->select(
                "SELECT status, COUNT(*) as count, SUM(penalty_points) as total_points
                 FROM violation_records
                 GROUP BY status"
            ),

            // Count by type
            'by_type' => $this->db->select(
                "SELECT violation_type, COUNT(*) as count
                 FROM violation_records
                 WHERE status = 'active'
                 GROUP BY violation_type
                 ORDER BY count DESC"
            ),

            // Count by severity
            'by_severity' => $this->db->select(
                "SELECT severity, COUNT(*) as count
                 FROM violation_records
                 WHERE status = 'active'
                 GROUP BY severity"
            ),

            // Students with violations
            'high_risk_students' => $this->db->select(
                "SELECT s.id, s.full_name, s.student_code,
                        SUM(v.penalty_points) as total_points
                 FROM students s
                 JOIN violation_records v ON v.student_id = s.id AND v.status = 'active'
                 GROUP BY s.id
                 HAVING total_points >= ?
                 ORDER BY total_points DESC
                 LIMIT 20",
                [VIOLATION_THRESHOLD]
            ),

            // Monthly trend
            'monthly_trend' => $this->db->select(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
                 FROM violation_records
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY month"
            ),

            // Total stats
            'total' => [
                'total_violations' => $this->db->selectValue("SELECT COUNT(*) FROM violation_records"),
                'total_active' => $this->db->selectValue(
                    "SELECT COUNT(*) FROM violation_records WHERE status = 'active'"
                ),
                'total_points' => $this->db->selectValue(
                    "SELECT SUM(penalty_points) FROM violation_records WHERE status = 'active'"
                ),
                'students_with_violations' => $this->db->selectValue(
                    "SELECT COUNT(DISTINCT student_id) FROM violation_records"
                ),
                'banned_students' => $this->db->selectValue(
                    "SELECT COUNT(DISTINCT student_id)
                     FROM violation_records
                     WHERE status = 'active'
                     GROUP BY student_id
                     HAVING SUM(penalty_points) >= ?",
                    [VIOLATION_THRESHOLD]
                ),
            ],
        ];

        $this->jsonOk($stats, 'Thống kê vi phạm');
    }

    /**
     * GET /api/violations/export
     * Xuất báo cáo vi phạm
     */
    public function export(array $params = []): void
    {
        $this->requireAdmin();

        $format = $this->request('format', 'csv');

        $violations = $this->db->select(
            "SELECT v.*, s.full_name, s.student_code
             FROM violation_records v
             JOIN students s ON s.id = v.student_id
             ORDER BY v.created_at DESC"
        );

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="violations_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Sinh viên', 'Mã SV', 'Loại vi phạm', 'Điểm', 'Trạng thái', 'Ngày ghi nhận']);

            foreach ($violations as $v) {
                fputcsv($output, [
                    $v['id'],
                    $v['full_name'],
                    $v['student_code'],
                    $v['violation_type'],
                    $v['penalty_points'],
                    $v['status'],
                    $v['created_at'],
                ]);
            }

            fclose($output);
            exit;
        } else {
            // JSON
            header('Content-Type: application/json');
            echo json_encode($violations);
            exit;
        }
    }
}
