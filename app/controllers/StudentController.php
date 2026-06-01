<?php
/**
 * app/controllers/StudentController.php
 * Student Portal Dashboard & Profile Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class StudentController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    public function dashboard(array $params = []): void
    {
        $userId = $this->auth('id');

        // 1. Lấy hồ sơ sinh viên
        $student = $this->db->selectOne("
            SELECT * FROM students WHERE user_id = ?
        ", [$userId]);

        if (!$student) {
            $this->abort(404, 'Không tìm thấy hồ sơ sinh viên.');
        }

        $studentId = (int)$student['id'];

        // 2. Lấy hợp đồng hoạt động (hoặc đang xem xét)
        $contract = $this->db->selectOne("
            SELECT c.*, r.room_number, b.name AS building_name
            FROM contracts c
            JOIN rooms r ON r.id = c.room_id
            JOIN buildings b ON b.id = r.building_id
            WHERE c.student_id = ? AND c.status IN ('active', 'under_review')
            LIMIT 1
        ", [$studentId]);

        // 3. Thống kê hóa đơn chưa thanh toán
        $unpaid_invoices = (int)$this->db->selectValue("
            SELECT COUNT(*) FROM invoices i
            JOIN contracts c ON c.id = i.contract_id
            WHERE c.student_id = ? AND i.status = 'unpaid'
        ", [$studentId]);

        // 4. Thống kê vi phạm hiệu lực (status = 'active')
        $active_violations = (int)$this->db->selectValue("
            SELECT COUNT(*) FROM violation_records
            WHERE student_id = ? AND status = 'active'
        ", [$studentId]);

        // 5. Lấy 5 thông báo gần nhất
        $recent_notifications = $this->db->select("
            SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY sent_at DESC LIMIT 5
        ", [$userId]);

        $this->view('student/dashboard', [
            'title'                => 'Trang chủ',
            'student'              => $student,
            'contract'             => $contract,
            'unpaid_invoices'      => $unpaid_invoices,
            'active_violations'    => $active_violations,
            'recent_notifications' => $recent_notifications
        ]);
    }

    public function profile(array $params = []): void
    {
        $userId  = $this->auth('id');
        $student = $this->db->selectOne("SELECT * FROM students WHERE user_id = ?", [$userId]);

        if (!$student) {
            $this->abort(404, 'Không tìm thấy hồ sơ sinh viên.');
        }

        $this->view('student/profile', [
            'title'   => 'Hồ sơ cá nhân',
            'student' => $student
        ]);
    }

    public function updateProfile(array $params = []): void
    {
        $this->verifyCsrf();
        $userId  = $this->auth('id');
        $student = $this->db->selectOne("SELECT * FROM students WHERE user_id = ?", [$userId]);

        if (!$student) {
            $this->jsonError('Sinh viên không tồn tại', 404);
        }

        $studentId = (int)$student['id'];
        $data = $this->only(['phone', 'hometown']);

        $errors = $this->validate($data, [
            'phone'    => 'required|max:15',
            'hometown' => 'required|max:200'
        ]);

        if (!empty($errors)) {
            $this->withOldInput($data);
            $this->withErrors($errors, '/student/profile');
            return;
        }

        try {
            $this->db->update('students', [
                'phone'    => $data['phone'],
                'hometown' => $data['hometown']
            ], 'id = ?', [$studentId]);

            $this->flash('success', 'Cập nhật hồ sơ thành công.');
            $this->redirect('/student/profile');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->flash('error', 'Lỗi khi cập nhật hồ sơ.');
            $this->back();
        }
    }
}
