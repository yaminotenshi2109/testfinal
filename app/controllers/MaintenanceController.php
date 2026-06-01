<?php
/**
 * app/controllers/MaintenanceController.php
 * Student Maintenance Requests Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class MaintenanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    public function myRequests(array $params = []): void
    {
        $userId = $this->auth('id');

        // Lấy hồ sơ sinh viên
        $student = $this->db->selectOne("SELECT id FROM students WHERE user_id = ?", [$userId]);
        if (!$student) {
            $this->abort(404, 'Không tìm thấy hồ sơ sinh viên');
        }

        $requests = $this->db->select("
            SELECT m.*, r.room_number, b.name AS building_name
            FROM maintenance_requests m
            JOIN rooms r ON r.id = m.room_id
            JOIN buildings b ON b.id = r.building_id
            WHERE m.reported_by = ?
            ORDER BY m.reported_at DESC
        ", [$userId]);

        $this->view('student/maintenance/index', [
            'title'    => 'Yêu cầu bảo trì',
            'requests' => $requests
        ]);
    }

    public function store(array $params = []): void
    {
        $this->verifyCsrf();
        $userId = $this->auth('id');

        // Lấy phòng hiện tại của sinh viên
        $student = $this->db->selectOne("SELECT id FROM students WHERE user_id = ?", [$userId]);
        if (!$student) {
            $this->jsonError('Sinh viên không tồn tại', 404);
        }

        $contract = $this->db->selectOne("
            SELECT room_id FROM contracts 
            WHERE student_id = ? AND status = 'active'
            LIMIT 1
        ", [(int)$student['id']]);

        if (!$contract) {
            $this->jsonError('Bạn cần có hợp đồng thuê phòng đang hoạt động để gửi yêu cầu bảo trì.', 409);
        }

        $data = $this->only(['title', 'description', 'priority']);

        $errors = $this->validate($data, [
            'title'       => 'required|max:200',
            'description' => 'required',
            'priority'    => 'required|in:low,medium,high,urgent'
        ]);

        if (!empty($errors)) {
            $this->jsonError('Dữ liệu không hợp lệ.', 422, $errors);
        }

        try {
            $this->db->insert('maintenance_requests', [
                'room_id'     => $contract['room_id'],
                'reported_by' => $userId,
                'title'       => $data['title'],
                'description' => $data['description'],
                'priority'    => $data['priority'],
                'status'      => 'open'
            ]);

            $this->jsonOk(null, 'Gửi yêu cầu bảo trì thành công.', 201);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi gửi yêu cầu.', 500);
        }
    }

    public function cancel(array $params = []): void
    {
        $id = (int)$params['id'];
        $userId = $this->auth('id');

        $request = $this->db->selectOne("SELECT * FROM maintenance_requests WHERE id = ?", [$id]);
        if (!$request) {
            $this->jsonError('Yêu cầu không tồn tại', 404);
        }

        if ((int)$request['reported_by'] !== $userId) {
            $this->jsonError('Bạn không có quyền hủy yêu cầu này', 403);
        }

        if ($request['status'] !== 'open') {
            $this->jsonError('Chỉ có thể hủy yêu cầu ở trạng thái đang chờ xử lý', 409);
        }

        try {
            $this->db->delete('maintenance_requests', 'id = ?', [$id]);
            $this->jsonOk(null, 'Hủy yêu cầu thành công.');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi hủy yêu cầu.', 500);
        }
    }
}
