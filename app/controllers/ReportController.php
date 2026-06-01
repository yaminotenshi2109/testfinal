<?php
/**
 * app/controllers/ReportController.php
 * Admin Reports & Analysis Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class ReportController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function revenue(array $params = []): void
    {
        // 1. Thống kê theo tháng / năm của hóa đơn đã thanh toán
        $monthlyRevenue = $this->db->select("
            SELECT year, month, SUM(total_amount) AS revenue, COUNT(*) AS invoice_count
            FROM invoices
            WHERE status = 'paid'
            GROUP BY year, month
            ORDER BY year DESC, month DESC
            LIMIT 12
        ");

        // 2. Thống kê phương thức thanh toán
        $paymentMethods = $this->db->select("
            SELECT payment_method, COUNT(*) AS count, SUM(total_amount) AS total
            FROM invoices
            WHERE status = 'paid' AND payment_method IS NOT NULL AND payment_method != ''
            GROUP BY payment_method
        ");

        // 3. Hóa đơn chưa thu (unpaid)
        $unpaidStats = $this->db->selectOne("
            SELECT COUNT(*) AS count, SUM(total_amount) AS total
            FROM invoices
            WHERE status = 'unpaid'
        ");

        $this->view('admin/reports/revenue', [
            'title'          => 'Báo cáo doanh thu',
            'monthlyRevenue' => $monthlyRevenue,
            'paymentMethods' => $paymentMethods,
            'unpaidStats'    => $unpaidStats
        ]);
    }

    public function occupancy(array $params = []): void
    {
        // Thống kê theo từng tòa nhà
        $occupancyByBuilding = $this->db->select("
            SELECT b.name AS building_name, b.gender_type,
                   (SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.id) AS room_count,
                   (SELECT SUM(capacity) FROM rooms r WHERE r.building_id = b.id) AS total_capacity,
                   (SELECT SUM(current_occupants) FROM rooms r WHERE r.building_id = b.id) AS total_occupants
            FROM buildings b
            ORDER BY b.name
        ");

        $this->jsonOk($occupancyByBuilding, 'Thống kê lấp đầy KTX.');
    }

    public function violations(array $params = []): void
    {
        // Danh sách sinh viên có nhiều điểm phạt nhất
        $topViolatingStudents = $this->db->select("
            SELECT s.full_name, s.student_code, SUM(v.penalty_points) AS total_points, COUNT(*) AS violation_count
            FROM violation_records v
            JOIN students s ON s.id = v.student_id
            WHERE v.status = 'active'
            GROUP BY s.id
            ORDER BY total_points DESC
            LIMIT 10
        ");

        $this->jsonOk($topViolatingStudents, 'Lịch sử thống kê vi phạm.');
    }
}
