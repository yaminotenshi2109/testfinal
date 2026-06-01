<?php
/**
 * app/controllers/InvoiceController.php
 * Student Invoice Management Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/BillingController.php';

class InvoiceController extends BillingController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    public function myInvoices(array $params = []): void
    {
        $this->studentList($params);
    }

    public function show(array $params = []): void
    {
        $id = (int)$params['id'];
        $userId = $this->auth('id');
        
        $student = $this->db->selectOne("SELECT id FROM students WHERE user_id = ?", [$userId]);
        if (!$student) {
            $this->abort(404, 'Không tìm thấy hồ sơ sinh viên');
        }

        $invoice = $this->db->selectOne("
            SELECT i.*, s.full_name, s.student_code, r.room_number, b.name as building_name, c.student_id
            FROM invoices i
            JOIN contracts c ON c.id = i.contract_id
            JOIN students s ON s.id = c.student_id
            JOIN rooms r ON r.id = c.room_id
            JOIN buildings b ON b.id = r.building_id
            WHERE i.id = ? AND c.student_id = ?
        ", [$id, $student['id']]);

        if (!$invoice) {
            $this->abort(404, 'Hóa đơn không tồn tại');
        }

        $this->view('student/invoices/show', [
            'title'   => 'Chi tiết hóa đơn #' . $id,
            'invoice' => $invoice,
        ]);
    }
}
