<?php
/**
 * app/controllers/AdminController.php
 * Admin Dashboard Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function dashboard(array $params = []): void
    {
        // 1. Thống kê tổng hợp
        $stats = [
            'total_rooms'           => (int)$this->db->selectValue("SELECT COUNT(*) FROM rooms"),
            'occupied_rooms'        => (int)$this->db->selectValue("SELECT COUNT(*) FROM rooms WHERE current_occupants > 0"),
            'available_rooms'       => (int)$this->db->selectValue("SELECT COUNT(*) FROM rooms WHERE status = 'available'"),
            'total_students'        => (int)$this->db->selectValue("SELECT COUNT(*) FROM students"),
            'total_contracts'       => (int)$this->db->selectValue("SELECT COUNT(*) FROM contracts WHERE status = 'active'"),
            'unpaid_invoices'       => (int)$this->db->selectValue("SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'"),
            'pending_registrations' => (int)$this->db->selectValue("SELECT COUNT(*) FROM room_registrations WHERE status = 'pending'"),
            'open_violations'       => (int)$this->db->selectValue("SELECT COUNT(*) FROM violation_records WHERE status = 'active'")
        ];

        // 2. Đơn đăng ký gần đây
        $recent_registrations = $this->db->select("
            SELECT r.*, s.full_name AS student_name, s.student_code, rm.room_number, b.name AS building_name
            FROM room_registrations r
            JOIN students s ON s.id = r.student_id
            LEFT JOIN rooms rm ON rm.id = r.assigned_room_id
            LEFT JOIN buildings b ON b.id = r.preferred_building_id
            ORDER BY r.created_at DESC LIMIT 5
        ");

        // 3. Vi phạm gần đây
        $recent_violations = $this->db->select("
            SELECT v.*, s.full_name AS student_name, rm.room_number, v.recorded_at AS created_at
            FROM violation_records v
            JOIN students s ON s.id = v.student_id
            JOIN contracts c ON c.id = v.contract_id
            JOIN rooms rm ON rm.id = c.room_id
            ORDER BY v.recorded_at DESC LIMIT 5
        ");

        $this->view('admin/dashboard', [
            'title'                => 'Dashboard',
            'stats'                => $stats,
            'recent_registrations' => $recent_registrations,
            'recent_violations'   => $recent_violations
        ]);
    }
}
