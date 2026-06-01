<?php
/**
 * app/controllers/StatsApiController.php
 * JSON API for Dashboard Statistics
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class StatsApiController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function dashboard(array $params = []): void
    {
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

        $this->jsonOk($stats, 'Thống kê tổng hợp hệ thống.');
    }

    public function revenue(array $params = []): void
    {
        $monthlyRevenue = $this->db->select("
            SELECT year, month, SUM(total_amount) AS revenue, COUNT(*) AS invoice_count
            FROM invoices
            WHERE status = 'paid'
            GROUP BY year, month
            ORDER BY year DESC, month DESC
            LIMIT 12
        ");
        $this->jsonOk($monthlyRevenue, 'Thống kê doanh thu theo tháng.');
    }

    public function occupancy(array $params = []): void
    {
        $occupancyByBuilding = $this->db->select("
            SELECT b.name AS building_name, b.gender_type,
                   (SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.id) AS room_count,
                   (SELECT SUM(capacity) FROM rooms r WHERE r.building_id = b.id) AS total_capacity,
                   (SELECT SUM(current_occupants) FROM rooms r WHERE r.building_id = b.id) AS total_occupants
            FROM buildings b
            ORDER BY b.name
        ");
        $this->jsonOk($occupancyByBuilding, 'Thống kê lấp đầy theo tòa.');
    }
}
