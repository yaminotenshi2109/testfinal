<?php
/**
 * app/controllers/ContractAdminController.php
 * Admin Contract Management Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class ContractAdminController extends BaseController
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
            $where .= ' AND (s.full_name LIKE ? OR r.room_number LIKE ?)';
            $args[] = "%{$search}%";
            $args[] = "%{$search}%";
        }

        $result = $this->db->paginate("
            SELECT c.*, s.full_name AS student_name, s.student_code,
                   r.room_number, b.name AS building_name
            FROM contracts c
            JOIN students s ON s.id = c.student_id
            JOIN rooms r ON r.id = c.room_id
            JOIN buildings b ON b.id = r.building_id
            WHERE {$where}
            ORDER BY c.created_at DESC
        ", $args, $page, $perPage);

        $this->view('admin/contracts/index', [
            'title'      => 'Quản lý hợp đồng',
            'contracts'  => $result['data'],
            'pagination' => $result,
            'search'     => $search
        ]);
    }

    public function show(array $params = []): void
    {
        $id = (int)$params['id'];
        $contract = $this->db->selectOne("
            SELECT c.*, s.full_name AS student_name, s.student_code, s.phone,
                   r.room_number, r.room_type, b.name AS building_name
            FROM contracts c
            JOIN students s ON s.id = c.student_id
            JOIN rooms r ON r.id = c.room_id
            JOIN buildings b ON b.id = r.building_id
            WHERE c.id = ?
        ", [$id]);

        if (!$contract) {
            $this->abort(404, 'Không tìm thấy hợp đồng.');
        }

        $this->view('admin/contracts/show', [
            'title'    => 'Hợp đồng: ' . htmlspecialchars($contract['student_name']),
            'contract' => $contract
        ]);
    }

    public function terminate(array $params = []): void
    {
        $this->verifyCsrf();
        $id = (int)$params['id'];
        $contract = $this->db->selectOne("SELECT * FROM contracts WHERE id = ?", [$id]);

        if (!$contract) {
            $this->jsonError('Hợp đồng không tồn tại', 404);
        }

        if ($contract['status'] !== 'active') {
            $this->jsonError('Hợp đồng đã chấm dứt trước đó', 409);
        }

        try {
            $this->db->transaction(function (Database $db) use ($id, $contract) {
                // 1. Cập nhật trạng thái hợp đồng
                $db->update('contracts', [
                    'status' => 'cancelled',
                    'end_date' => date('Y-m-d')
                ], 'id = ?', [$id]);

                // 2. Giảm số lượng sinh viên trong phòng (Trigger handles this on insert/delete, but let's manual update for update status if no trigger on status)
                $db->query("
                    UPDATE rooms 
                    SET current_occupants = GREATEST(0, current_occupants - 1),
                        status = 'available'
                    WHERE id = ?
                ", [$contract['room_id']]);

                // 3. Gửi thông báo cho sinh viên
                $student = $db->selectOne("SELECT user_id FROM students WHERE id = ?", [$contract['student_id']]);
                if ($student) {
                    $db->insert('notifications', [
                        'user_id' => $student['user_id'],
                        'title'   => 'Hợp đồng thuê phòng đã chấm dứt',
                        'message' => 'Hợp đồng của bạn đã được ban quản lý chấm dứt.',
                        'type'    => 'contract'
                    ]);
                }
            });

            $this->jsonOk(null, 'Chấm dứt hợp đồng thành công.');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi chấm dứt hợp đồng', 500);
        }
    }
}
