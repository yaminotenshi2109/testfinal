<?php
/**
 * app/controllers/RegistrationAdminController.php
 * Admin Room Registration Management Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/RegistrationController.php';

class RegistrationAdminController extends RegistrationController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function pending(array $params = []): void
    {
        $_GET['status'] = 'pending';
        $this->index($params);
    }

    public function show(array $params = []): void
    {
        $id = (int)$params['id'];
        $registration = $this->db->selectOne("
            SELECT r.*, s.full_name, s.student_code, s.gender, s.priority_level, s.phone,
                   b.name AS building_name,
                   rm.room_number, rm.floor
            FROM room_registrations r
            JOIN students s ON s.id = r.student_id
            LEFT JOIN buildings b ON b.id = r.preferred_building_id
            LEFT JOIN rooms rm ON rm.id = r.assigned_room_id
            WHERE r.id = ?
        ", [$id]);

        if (!$registration) {
            $this->abort(404, 'Đơn đăng ký không tồn tại');
        }

        // Lấy danh sách các phòng còn trống của tòa nhà ưa thích để gán thủ công
        $availableRooms = $this->db->select("
            SELECT r.*, b.name AS building_name
            FROM rooms r
            JOIN buildings b ON b.id = r.building_id
            WHERE r.status = 'available' AND r.current_occupants < r.capacity
            ORDER BY b.name, r.room_number
        ");

        $this->view('admin/registrations/show', [
            'title'          => 'Chi tiết đăng ký phòng',
            'registration'   => $registration,
            'availableRooms' => $availableRooms
        ]);
    }

    public function approve(array $params = []): void
    {
        $roomId = (int)$this->request('room_id', 0);
        if ($roomId > 0) {
            $this->manualAllocate($params);
        } else {
            $this->autoAllocate($params);
        }
    }
}
