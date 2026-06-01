<?php
/**
 * app/controllers/ContractController.php
 * Student Contract View Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class ContractController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(array $params = []): void
    {
        $userId = $this->auth('id');
        $student = $this->db->selectOne("SELECT id FROM students WHERE user_id = ?", [$userId]);

        if (!$student) {
            $this->abort(404, 'Không tìm thấy hồ sơ sinh viên.');
        }

        $studentId = (int)$student['id'];

        $contracts = $this->db->select("
            SELECT c.*, rm.room_number, b.name AS building_name
            FROM contracts c
            JOIN rooms rm ON rm.id = c.room_id
            JOIN buildings b ON b.id = rm.building_id
            WHERE c.student_id = ?
            ORDER BY c.created_at DESC
        ", [$studentId]);

        $this->view('student/contracts/index', [
            'title'     => 'Hợp đồng của tôi',
            'contracts' => $contracts
        ]);
    }

    public function show(array $params = []): void
    {
        $userId = $this->auth('id');
        $student = $this->db->selectOne("SELECT id FROM students WHERE user_id = ?", [$userId]);

        if (!$student) {
            $this->abort(404, 'Không tìm thấy hồ sơ sinh viên.');
        }

        $studentId = (int)$student['id'];
        $id = (int)$params['id'];

        $contract = $this->db->selectOne("
            SELECT c.*, rm.room_number, rm.room_type, b.name AS building_name, b.manager_name, b.manager_phone
            FROM contracts c
            JOIN rooms rm ON rm.id = c.room_id
            JOIN buildings b ON b.id = rm.building_id
            WHERE c.id = ? AND c.student_id = ?
        ", [$id, $studentId]);

        if (!$contract) {
            $this->abort(404, 'Hợp đồng không tồn tại.');
        }

        $this->view('student/contracts/show', [
            'title'    => 'Chi tiết hợp đồng',
            'contract' => $contract
        ]);
    }
}
