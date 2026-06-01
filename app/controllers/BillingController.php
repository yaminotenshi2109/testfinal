<?php
/**
 * app/controllers/BillingController.php
 * ─────────────────────────────────────────────────────────────
 *  Quản lý hóa đơn (CRUD + PDF + Statistics)
 *
 *  Endpoints:
 *  • GET /admin/invoices — Danh sách hóa đơn
 *  • GET /admin/invoices/:id — Chi tiết hóa đơn
 *  • POST /api/invoices/generate — Tạo hóa đơn
 *  • POST /api/invoices/:id/mark-paid — Đánh dấu thanh toán
 *  • GET /api/invoices/:id/pdf — Download PDF
 *  • GET /api/invoices/stats — Thống kê
 *  • GET /student/invoices — Sinh viên xem hóa đơn
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Models.php';
require_once __DIR__ . '/../services/BillingService.php';
require_once __DIR__ . '/../services/InvoicePdfGenerator.php';

class BillingController extends BaseController
{
    private BillingService $billingService;
    private InvoicePdfGenerator $pdfGenerator;
    private InvoiceModel $invoiceModel;

    public function __construct()
    {
        parent::__construct();
        $this->billingService = new BillingService();
        $this->pdfGenerator = new InvoicePdfGenerator();
        $this->invoiceModel = new InvoiceModel();
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  ADMIN VIEWS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /admin/invoices
     * Danh sách hóa đơn
     */
    public function index(array $params = []): void
    {
        $this->requireAdmin();

        [$page, $perPage] = $this->paginationParams();

        // Filters
        $status = $this->request('status', '');
        $search = $this->request('q', '');

        $where = '1';
        $args = [];

        if ($status) {
            $where .= ' AND i.status = ?';
            $args[] = $status;
        }

        if ($search) {
            $where .= ' AND (s.full_name LIKE ? OR s.student_code LIKE ?)';
            $searchParam = "%{$search}%";
            $args[] = $searchParam;
            $args[] = $searchParam;
        }

        $result = $this->db->paginate(
            "SELECT i.*, s.full_name, s.student_code, r.room_number
             FROM invoices i
             JOIN contracts c ON c.id = i.contract_id
             JOIN students s ON s.id = c.student_id
             JOIN rooms r ON r.id = c.room_id
             WHERE {$where}
             ORDER BY i.created_at DESC",
            $args,
            $page,
            $perPage
        );

        $this->view('admin/invoices/index', [
            'title'       => 'Quản lý hóa đơn',
            'invoices'    => $result['data'],
            'pagination'  => $result,
            'status'      => $status,
            'search'      => $search,
            'statuses'    => ['paid' => 'Đã thanh toán', 'unpaid' => 'Chưa thanh toán', 'overdue' => 'Quá hạn'],
        ]);
    }

    /**
     * GET /admin/invoices/:id
     * Chi tiết hóa đơn
     */
    public function show(array $params = []): void
    {
        $this->requireAdmin();

        $id = (int)$params['id'];

        $invoice = $this->db->selectOne(
            "SELECT i.*, s.full_name, s.student_code, r.room_number, b.name as building_name
             FROM invoices i
             JOIN contracts c ON c.id = i.contract_id
             JOIN students s ON s.id = c.student_id
             JOIN rooms r ON r.id = c.room_id
             JOIN buildings b ON b.id = r.building_id
             WHERE i.id = ?",
            [$id]
        );

        if (!$invoice) {
            $this->abort(404, 'Hóa đơn không tồn tại');
        }

        $this->view('admin/invoices/show', [
            'title'   => 'Chi tiết hóa đơn #' . $id,
            'invoice' => $invoice,
        ]);
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  AJAX API ENDPOINTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * POST /api/invoices/generate
     * Tạo hóa đơn
     *
     * Request:
     *   {
     *     "contract_id": 5,
     *     "month": 5,
     *     "year": 2026
     *   }
     */
    public function generateInvoice(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $contractId = (int)$this->request('contract_id');
        $month = (int)$this->request('month');
        $year = (int)$this->request('year');

        // Validate
        if (!$contractId || !$month || !$year) {
            $this->jsonError('Dữ liệu không đủ', 422);
        }

        if ($month < 1 || $month > 12) {
            $this->jsonError('Tháng không hợp lệ', 422);
        }

        $result = $this->billingService->generateInvoice($contractId, $month, $year);

        if (!$result['success']) {
            $this->jsonError($result['message'], 422);
        }

        $this->jsonOk($result['data'], 'Tạo hóa đơn thành công', 201);
    }

    /**
     * POST /api/invoices/generate-batch
     * Tạo hóa đơn cho tất cả sinh viên trong một tháng
     *
     * Request:
     *   {
     *     "month": 5,
     *     "year": 2026
     *   }
     */
    public function generateBatch(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $month = (int)$this->request('month');
        $year = (int)$this->request('year');

        $result = $this->billingService->generateMonthlyBatch($month, $year);

        $this->jsonOk($result, 'Tạo hóa đơn hàng loạt', 201);
    }

    /**
     * POST /api/invoices/:id/mark-paid
     * Đánh dấu hóa đơn đã thanh toán
     *
     * Request:
     *   {
     *     "payment_method": "bank",
     *     "transaction_id": "TRX123456"
     *   }
     */
    public function markPaid(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];
        $paymentMethod = $this->request('payment_method', 'cash');
        $transactionId = $this->request('transaction_id');

        $result = $this->billingService->markInvoicePaid($id, $paymentMethod, $transactionId);

        if (!$result['success']) {
            $this->jsonError($result['message'], 422);
        }

        $this->jsonOk($result['data'], 'Đánh dấu thanh toán thành công');
    }

    /**
     * GET /api/invoices/:id/pdf
     * Download PDF hóa đơn
     */
    public function getPdf(array $params = []): void
    {
        $this->requireAuth();

        $id = (int)$params['id'];

        // Check access: admin or invoice owner
        // Fixed: joined through contracts since invoices has no direct student_id column
        $invoice = $this->db->selectOne(
            "SELECT i.*, s.user_id, s.full_name, s.student_code, r.room_number, b.name as building_name
             FROM invoices i
             JOIN contracts c ON c.id = i.contract_id
             JOIN students s ON s.id = c.student_id
             JOIN rooms r ON r.id = c.room_id
             JOIN buildings b ON b.id = r.building_id
             WHERE i.id = ?",
            [$id]
        );

        if (!$invoice) {
            $this->abort(404, 'Hóa đơn không tồn tại');
        }

        if (!$this->isAdmin() && (int)$invoice['user_id'] !== (int)$this->auth('id')) {
            $this->abort(403, 'Không có quyền');
        }

        // If FPDF autoload is not available, render a beautiful printable HTML invoice instead of crashing!
        if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
            $this->view('admin/invoices/print', [
                'title'   => 'In hóa đơn #' . $id,
                'invoice' => $invoice,
            ], false); // Không dùng main layout, dùng layout in riêng biệt
            return;
        }

        // Generate PDF using temp directory (cross-platform safe: sys_get_temp_dir())
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'invoice_' . $id . '_' . time() . '.pdf';

        if ($this->pdfGenerator->generate($id, $tempFile)) {
            // Send file
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="invoice_' . $id . '.pdf"');
            header('Content-Length: ' . filesize($tempFile));

            readfile($tempFile);

            // Clean up
            unlink($tempFile);
            exit;
        } else {
            $this->abort(500, 'Lỗi tạo PDF');
        }
    }

    /**
     * GET /api/invoices/stats
     * Thống kê hóa đơn
     */
    public function stats(array $params = []): void
    {
        $this->requireAdmin();

        $paymentStats = $this->billingService->getPaymentStats();
        $monthlyRevenue = $this->billingService->getMonthlyRevenue(12);

        $this->jsonOk([
            'payment_stats'    => $paymentStats,
            'monthly_revenue'  => $monthlyRevenue,
        ], 'Thống kê hóa đơn');
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  STUDENT ENDPOINTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /student/invoices
     * Sinh viên xem hóa đơn
     */
    public function studentList(array $params = []): void
    {
        $this->requireAuth();

        $userId = $this->auth('id');
        $student = $this->db->selectOne(
            "SELECT id FROM students WHERE user_id = ?",
            [$userId]
        );

        if (!$student) {
            $this->abort(404, 'Không tìm thấy hồ sơ sinh viên');
        }

        $invoices = $this->db->select(
            "SELECT i.*, r.room_number, b.name as building_name
             FROM invoices i
             JOIN contracts c ON c.id = i.contract_id
             JOIN rooms r ON r.id = c.room_id
             JOIN buildings b ON b.id = r.building_id
             WHERE c.student_id = ?
             ORDER BY i.created_at DESC",
            [$student['id']]
        );

        // Calculate totals
        $totalAmount = array_sum(array_map(fn($i) => $i['total_amount'], $invoices));
        $unpaidAmount = array_sum(
            array_map(
                fn($i) => $i['status'] !== 'paid' ? $i['total_amount'] : 0,
                $invoices
            )
        );

        $this->view('student/invoices/index', [
            'title'          => 'Hóa đơn của tôi',
            'invoices'       => $invoices,
            'total_amount'   => $totalAmount,
            'unpaid_amount'  => $unpaidAmount,
        ]);
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  HELPERS
     * ───────────────────────────────────────────────────────────
     */

    private function isAdmin(): bool
    {
        return $this->auth('role') === 'admin';
    }
}
