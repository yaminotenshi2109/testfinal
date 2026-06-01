<?php
/**
 * app/controllers/StudentApiController.php
 * JSON API for Student Records
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class StudentApiController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function index(array $params = []): void
    {
        $students = $this->db->select("
            SELECT s.*, u.email, u.status AS user_status
            FROM students s
            JOIN users u ON u.id = s.user_id
            ORDER BY s.student_code
        ");
        $this->jsonOk($students, 'Lấy danh sách sinh viên.');
    }

    public function show(array $params = []): void
    {
        $id = (int)$params['id'];
        $student = $this->db->selectOne("
            SELECT s.*, u.email, u.status AS user_status
            FROM students s
            JOIN users u ON u.id = s.user_id
            WHERE s.id = ?
        ", [$id]);

        if (!$student) {
            $this->jsonError('Sinh viên không tồn tại', 404);
        }

        $this->jsonOk($student, 'Lấy chi tiết sinh viên.');
    }

    public function update(array $params = []): void
    {
        $this->verifyCsrf();
        $id = (int)$params['id'];
        $student = $this->db->selectOne("SELECT * FROM students WHERE id = ?", [$id]);

        if (!$student) {
            $this->jsonError('Sinh viên không tồn tại', 404);
        }

        $data = $this->only(['full_name', 'phone', 'priority_level', 'faculty', 'program']);

        try {
            $this->db->update('students', [
                'full_name'      => $data['full_name'] ?? $student['full_name'],
                'phone'          => $data['phone'] ?? $student['phone'],
                'priority_level' => (int)($data['priority_level'] ?? $student['priority_level']),
                'faculty'        => $data['faculty'] ?? $student['faculty'],
                'program'        => $data['program'] ?? $student['program']
            ], 'id = ?', [$id]);

            $this->jsonOk(null, 'Cập nhật sinh viên thành công.');
        } catch (\Throwable $e) {
            $this->jsonError('Lỗi cập nhật', 500);
        }
    }
}
