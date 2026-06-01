<?php
/**
 * app/controllers/MaintenanceAdminController.php
 * Admin Maintenance Requests Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class MaintenanceAdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function index(array $params = []): void
    {
        [$page, $perPage] = $this->paginationParams();

        $status = $this->request('status', '');
        $priority = $this->request('priority', '');

        $where = '1';
        $args = [];

        if ($status) {
            $where .= ' AND m.status = ?';
            $args[] = $status;
        }

        if ($priority) {
            $where .= ' AND m.priority = ?';
            $args[] = $priority;
        }

        $result = $this->db->paginate("
            SELECT m.*, r.room_number, b.name AS building_name, u.username AS reporter_username
            FROM maintenance_requests m
            JOIN rooms r ON r.id = m.room_id
            JOIN buildings b ON b.id = r.building_id
            JOIN users u ON u.id = m.reported_by
            WHERE {$where}
            ORDER BY m.reported_at DESC
        ", $args, $page, $perPage);

        $this->view('admin/maintenance/index', [
            'title'      => 'Quản lý bảo trì',
            'requests'   => $result['data'],
            'pagination' => $result,
            'status'     => $status,
            'priority'   => $priority
        ]);
    }

    public function show(array $params = []): void
    {
        $id = (int)$params['id'];
        $request = $this->db->selectOne("
            SELECT m.*, r.room_number, b.name AS building_name, 
                   u.username AS reporter_username, s.full_name AS reporter_name, s.phone AS reporter_phone
            FROM maintenance_requests m
            JOIN rooms r ON r.id = m.room_id
            JOIN buildings b ON b.id = r.building_id
            JOIN users u ON u.id = m.reported_by
            LEFT JOIN students s ON s.user_id = u.id
            WHERE m.id = ?
        ", [$id]);

        if (!$request) {
            $this->abort(404, 'Yêu cầu không tồn tại');
        }

        $this->view('admin/maintenance/show', [
            'title'   => 'Chi tiết sự cố #' . $id,
            'request' => $request
        ]);
    }

    public function resolve(array $params = []): void
    {
        $this->verifyCsrf();
        $id = (int)$params['id'];
        $resolution = $this->request('resolution', '');

        if (!$resolution) {
            $this->jsonError('Vui lòng nhập phương án xử lý.', 422);
        }

        try {
            $this->db->update('maintenance_requests', [
                'status'      => 'resolved',
                'resolution'  => $resolution,
                'resolved_by' => $this->auth('id'),
                'resolved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);

            $this->jsonOk(null, 'Đã cập nhật trạng thái xử lý thành công.');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi xử lý yêu cầu.', 500);
        }
    }

    public function close(array $params = []): void
    {
        $this->verifyCsrf();
        $id = (int)$params['id'];

        try {
            $this->db->update('maintenance_requests', [
                'status' => 'closed'
            ], 'id = ?', [$id]);

            $this->jsonOk(null, 'Đã đóng yêu cầu thành công.');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi đóng yêu cầu.', 500);
        }
    }
}
