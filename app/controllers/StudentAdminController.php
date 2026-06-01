<?php
/**
 * app/controllers/StudentAdminController.php
 * Admin Student Management Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class StudentAdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function index(array $params = []): void
    {
        [$page, $perPage] = $this->paginationParams();
        $search = $this->request('q', '');

        $where = '1';
        $args = [];
        if ($search) {
            $where .= ' AND (s.full_name LIKE ? OR s.student_code LIKE ? OR s.phone LIKE ?)';
            $args[] = "%{$search}%";
            $args[] = "%{$search}%";
            $args[] = "%{$search}%";
        }

        $result = $this->db->paginate("
            SELECT s.*, u.email, u.status AS user_status,
                   rm.room_number, b.name AS building_name
            FROM students s
            JOIN users u ON u.id = s.user_id
            LEFT JOIN contracts c ON c.student_id = s.id AND c.status = 'active'
            LEFT JOIN rooms rm ON rm.id = c.room_id
            LEFT JOIN buildings b ON b.id = rm.building_id
            WHERE {$where}
            ORDER BY s.student_code
        ", $args, $page, $perPage);

        $this->view('admin/students/index', [
            'title'      => 'Quản lý sinh viên',
            'students'   => $result['data'],
            'pagination' => $result,
            'search'     => $search
        ]);
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
            $this->abort(404, 'Không tìm thấy sinh viên.');
        }

        // Lấy lịch sử hợp đồng
        $contracts = $this->db->select("
            SELECT c.*, rm.room_number, b.name AS building_name
            FROM contracts c
            JOIN rooms rm ON rm.id = c.room_id
            JOIN buildings b ON b.id = rm.building_id
            WHERE c.student_id = ?
            ORDER BY c.created_at DESC
        ", [$id]);

        // Lấy lịch sử vi phạm
        $violations = $this->db->select("
            SELECT * FROM violation_records
            WHERE student_id = ?
            ORDER BY recorded_at DESC
        ", [$id]);

        $this->view('admin/students/show', [
            'title'      => 'Chi tiết sinh viên: ' . htmlspecialchars($student['full_name']),
            'student'    => $student,
            'contracts'  => $contracts,
            'violations' => $violations
        ]);
    }

    public function violations(array $params = []): void
    {
        $id = (int)$params['id'];
        $student = $this->db->selectOne("SELECT * FROM students WHERE id = ?", [$id]);

        if (!$student) {
            $this->abort(404, 'Không tìm thấy sinh viên.');
        }

        $violations = $this->db->select("
            SELECT * FROM violation_records
            WHERE student_id = ?
            ORDER BY recorded_at DESC
        ", [$id]);

        $this->jsonOk($violations, 'Lấy danh sách vi phạm của sinh viên.');
    }
}
