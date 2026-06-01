<?php
/**
 * app/controllers/RegistrationController.php
 * ─────────────────────────────────────────────────────────────
 *  Quản lý đơn đăng ký phòng (tích hợp tự động gán phòng)
 *  
 *  Chức năng:
 *  • Student: Tạo đơn đăng ký phòng
 *  • Admin: Duyệt đơn, tự động gán phòng, xem báo cáo
 *  • AJAX CRUD: Không tải lại trang
 *
 *  Thành viên 2 phụ trách (Registration module)
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Models.php';
require_once __DIR__ . '/../services/RoomAllocationService.php';

class RegistrationController extends BaseController
{
    private RoomAllocationService $allocationService;
    private StudentModel $studentModel;

    public function __construct()
    {
        parent::__construct();
        $this->allocationService = new RoomAllocationService();
        $this->studentModel = new StudentModel();
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  STUDENT ENDPOINTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /student/registrations
     * Xem danh sách đơn đăng ký của sinh viên
     */
    public function studentList(array $params = []): void
    {
        $this->requireAuth();

        $userId = $this->auth('id');
        $student = $this->db->selectOne(
            "SELECT id FROM students WHERE user_id = ?",
            [$userId]
        );

        if (!$student) {
            $this->abort(404, 'Không tìm thấy hồ sơ sinh viên');
        }

        $registrations = $this->db->select(
            "SELECT r.*, b.name AS building_name, b.gender_type,
                    rm.room_number, rm.floor, rm.capacity
             FROM room_registrations r
             LEFT JOIN buildings b ON b.id = r.preferred_building_id
             LEFT JOIN rooms rm ON rm.id = r.assigned_room_id
             WHERE r.student_id = ?
             ORDER BY r.created_at DESC",
            [$student['id']]
        );

        $this->view('student/registrations/index', [
            'title'        => 'Đơn đăng ký phòng của tôi',
            'registrations' => $registrations,
        ]);
    }

    /**
     * POST /api/student/registrations
     * Tạo đơn đăng ký phòng mới
     *
     * Request:
     *   {
     *     "preferred_building_id": 1,   // optional
     *     "preferred_room_type": "standard",
     *     "notes": "..."
     *   }
     */
    public function studentStore(array $params = []): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $userId = $this->auth('id');

        // Lấy student profile
        $student = $this->db->selectOne(
            "SELECT id, full_name FROM students WHERE user_id = ?",
            [$userId]
        );

        if (!$student) {
            $this->jsonError('Không tìm thấy hồ sơ sinh viên', 404);
        }

        // Kiểm tra học kỳ hiện tại
        $currentSemester = $this->getCurrentSemester();

        // Kiểm tra đã có đơn đăng ký chưa
        $existing = $this->db->selectOne(
            "SELECT id FROM room_registrations
             WHERE student_id = ? AND semester = ? AND academic_year = ?
             AND status IN ('pending', 'approved')",
            [$student['id'], $currentSemester['semester'], $currentSemester['year']]
        );

        if ($existing) {
            $this->jsonError('Bạn đã có đơn đăng ký trong học kỳ này', 409);
        }

        $data = $this->only([
            'preferred_building_id',
            'preferred_room_type',
            'notes',
        ]);

        // Validation
        $errors = [];

        if (!empty($data['preferred_building_id'])) {
            $building = $this->db->selectOne(
                "SELECT id FROM buildings WHERE id = ? AND status = 'active'",
                [(int)$data['preferred_building_id']]
            );

            if (!$building) {
                $errors['preferred_building_id'] = ['Tòa nhà không hợp lệ'];
            }
        }

        if (!empty($data['preferred_room_type'])) {
            $valid = in_array(
                $data['preferred_room_type'],
                ['standard', 'deluxe', 'ac_standard', 'ac_deluxe']
            );

            if (!$valid) {
                $errors['preferred_room_type'] = ['Loại phòng không hợp lệ'];
            }
        }

        if (!empty($errors)) {
            $this->jsonError('Dữ liệu không hợp lệ', 422, $errors);
        }

        try {
            $registrationId = $this->db->transaction(function (Database $db) use (
                $student,
                $data,
                $currentSemester
            ) {
                // Tạo đơn đăng ký
                return $db->insert('room_registrations', [
                    'student_id'             => $student['id'],
                    'semester'               => $currentSemester['semester'],
                    'academic_year'          => $currentSemester['year'],
                    'preferred_building_id'  => !empty($data['preferred_building_id']) 
                                                ? (int)$data['preferred_building_id'] 
                                                : null,
                    'preferred_room_type'    => $data['preferred_room_type'] ?? null,
                    'notes'                  => $data['notes'] ?? null,
                    'status'                 => 'pending',
                    'created_at'             => date('Y-m-d H:i:s'),
                ]);
            });

            // Gửi thông báo
            $this->db->insert('notifications', [
                'user_id' => $userId,
                'title'   => 'Đơn đăng ký phòng được tạo',
                'message' => "Đơn đăng ký phòng của bạn đã được tạo. "
                           . "Vui lòng đợi admin duyệt.",
                'type'    => 'registration',
            ]);

            $this->jsonOk(
                ['registration_id' => $registrationId],
                'Tạo đơn đăng ký thành công',
                201
            );
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi tạo đơn đăng ký', 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  ADMIN ENDPOINTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /admin/registrations
     * Danh sách các đơn đăng ký (chỉ admin)
     */
    public function index(array $params = []): void
    {
        $this->requireAdmin();

        [$page, $perPage] = $this->paginationParams();

        // Filters
        $status = $this->request('status', '');
        $semester = $this->request('semester', '');

        $where = '1';
        $args = [];

        if ($status) {
            $where .= ' AND r.status = ?';
            $args[] = $status;
        }

        if ($semester) {
            [$sem, $year] = explode('/', $semester);
            $where .= ' AND r.semester = ? AND r.academic_year = ?';
            $args[] = $sem;
            $args[] = $year;
        }

        $result = $this->db->paginate(
            "SELECT r.*, s.full_name, s.gender, s.priority_level,
                    b.name AS building_name,
                    rm.room_number, rm.floor
             FROM room_registrations r
             JOIN students s ON s.id = r.student_id
             LEFT JOIN buildings b ON b.id = r.preferred_building_id
             LEFT JOIN rooms rm ON rm.id = r.assigned_room_id
             WHERE {$where}
             ORDER BY r.created_at DESC",
            $args,
            $page,
            $perPage
        );

        $this->view('admin/registrations/index', [
            'title'        => 'Quản lý đơn đăng ký phòng',
            'registrations' => $result['data'],
            'pagination'   => $result,
            'status'       => $status,
            'semester'     => $semester,
        ]);
    }

    /**
     * POST /api/registrations/:id/auto-allocate
     * Tự động gán phòng cho đơn đăng ký
     *
     * Request:
     *   { "method": "auto" } // or "manual" with room_id
     */
    public function autoAllocate(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $registrationId = (int)$params['id'];

        // Lấy đơn đăng ký
        $registration = $this->db->selectOne(
            "SELECT * FROM room_registrations WHERE id = ?",
            [$registrationId]
        );

        if (!$registration) {
            $this->jsonError('Đơn đăng ký không tồn tại', 404);
        }

        if ($registration['status'] !== 'pending') {
            $this->jsonError(
                'Chỉ có thể gán phòng cho đơn đăng ký chưa duyệt',
                409
            );
        }

        // Tự động gán phòng
        $result = $this->allocationService->allocateRoom(
            (int)$registration['student_id'],
            $registrationId
        );

        if (!$result['success']) {
            $this->jsonError($result['message'], 422);
        }

        $this->jsonOk(
            $result['data'],
            'Tự động gán phòng thành công',
            201
        );
    }

    /**
     * POST /api/registrations/:id/manual-allocate
     * Gán phòng thủ công
     *
     * Request:
     *   { "room_id": 5 }
     */
    public function manualAllocate(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $registrationId = (int)$params['id'];
        $roomId = (int)$this->request('room_id');

        if (!$roomId) {
            $this->jsonError('room_id là bắt buộc', 422);
        }

        // Lấy đơn đăng ký
        $registration = $this->db->selectOne(
            "SELECT * FROM room_registrations WHERE id = ?",
            [$registrationId]
        );

        if (!$registration) {
            $this->jsonError('Đơn đăng ký không tồn tại', 404);
        }

        // Gán phòng thủ công
        $result = $this->allocationService->allocateRoomManual(
            (int)$registration['student_id'],
            $roomId,
            $registrationId
        );

        if (!$result['success']) {
            $this->jsonError($result['message'], 422);
        }

        $this->jsonOk($result['data'], 'Gán phòng thủ công thành công');
    }

    /**
     * POST /api/registrations/:id/reject
     * Từ chối đơn đăng ký
     *
     * Request:
     *   { "reason": "..." }
     */
    public function reject(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $registrationId = (int)$params['id'];
        $reason = $this->request('reason', '');

        if (!$reason) {
            $this->jsonError('reason là bắt buộc', 422);
        }

        // Lấy đơn đăng ký
        $registration = $this->db->selectOne(
            "SELECT r.*, s.user_id FROM room_registrations r
             JOIN students s ON s.id = r.student_id
             WHERE r.id = ?",
            [$registrationId]
        );

        if (!$registration) {
            $this->jsonError('Đơn đăng ký không tồn tại', 404);
        }

        try {
            $this->db->transaction(function (Database $db) use (
                $registrationId,
                $reason,
                $registration
            ) {
                // Từ chối đơn
                $db->update(
                    'room_registrations',
                    [
                        'status'        => 'rejected',
                        'reject_reason' => $reason,
                        'reviewed_at'   => date('Y-m-d H:i:s'),
                        'reviewed_by'   => $this->auth('id'),
                    ],
                    'id = ?',
                    [$registrationId]
                );

                // Gửi thông báo
                $db->insert('notifications', [
                    'user_id' => $registration['user_id'],
                    'title'   => 'Đơn đăng ký phòng bị từ chối',
                    'message' => "Lý do: {$reason}",
                    'type'    => 'registration',
                ]);
            });

            $this->jsonOk(null, 'Từ chối đơn đăng ký thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi từ chối đơn', 500);
        }
    }

    /**
     * GET /api/registrations/stats
     * Thống kê tình trạng lấp đầy
     */
    public function stats(array $params = []): void
    {
        $this->requireAdmin();

        $stats = $this->allocationService->getOccupancyStats();
        $available = $this->allocationService->getAvailableRoomsReport();

        $this->jsonOk([
            'occupancy'    => $stats,
            'available'    => $available,
        ], 'Thống kê lấp đầy');
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  HELPER METHODS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * Lấy học kỳ hiện tại
     *
     * @return array { "semester": int, "year": int }
     */
    private function getCurrentSemester(): array
    {
        $month = (int)date('m');

        // VN academic year: 9/2024 - 5/2025
        // Semester 1: 9-12, Semester 2: 1-5
        if ($month >= 9) {
            $year = (int)date('Y');
            $semester = 'HK1';
        } elseif ($month >= 1 && $month <= 5) {
            $year = (int)date('Y') - 1;
            $semester = 'HK2';
        } else {
            // Summer: use HKH
            $year = (int)date('Y') - 1;
            $semester = 'HKH';
        }

        return [
            'semester' => $semester,
            'year'     => $year,
        ];
    }
}