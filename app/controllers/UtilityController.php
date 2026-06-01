<?php
/**
 * app/controllers/UtilityController.php
 * Admin Electricity and Water Readings Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class UtilityController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function index(array $params = []): void
    {
        [$page, $perPage] = $this->paginationParams();

        $result = $this->db->paginate("
            SELECT u.*, r.room_number, b.name AS building_name, us.username AS recorder_username
            FROM utility_readings u
            JOIN rooms r ON r.id = u.room_id
            JOIN buildings b ON b.id = r.building_id
            JOIN users us ON us.id = u.recorded_by
            ORDER BY u.year DESC, u.month DESC, b.name, r.room_number
        ", [], $page, $perPage);

        $this->view('admin/utilities/index', [
            'title'      => 'Chỉ số điện nước',
            'readings'   => $result['data'],
            'pagination' => $result
        ]);
    }

    public function create(array $params = []): void
    {
        $rooms = $this->db->select("
            SELECT r.*, b.name AS building_name 
            FROM rooms r
            JOIN buildings b ON b.id = r.building_id
            ORDER BY b.name, r.room_number
        ");

        $this->view('admin/utilities/create', [
            'title' => 'Ghi chỉ số điện nước mới',
            'rooms' => $rooms
        ]);
    }

    public function store(array $params = []): void
    {
        $this->verifyCsrf();
        $data = $this->only([
            'room_id', 'month', 'year', 
            'elec_prev', 'elec_curr', 
            'water_prev', 'water_curr', 
            'elec_rate', 'water_rate', 
            'notes'
        ]);

        $errors = $this->validate($data, [
            'room_id'    => 'required|integer',
            'month'      => 'required|integer|min:1|max:12',
            'year'       => 'required|integer|min:2020|max:2100',
            'elec_prev'  => 'required|numeric|min:0',
            'elec_curr'  => 'required|numeric|min:0',
            'water_prev' => 'required|numeric|min:0',
            'water_curr' => 'required|numeric|min:0'
        ]);

        if ((float)$data['elec_curr'] < (float)$data['elec_prev']) {
            $errors['elec_curr'][] = 'Chỉ số điện cuối kỳ không thể nhỏ hơn đầu kỳ.';
        }
        if ((float)$data['water_curr'] < (float)$data['water_prev']) {
            $errors['water_curr'][] = 'Chỉ số nước cuối kỳ không thể nhỏ hơn đầu kỳ.';
        }

        // Check unique
        $exists = $this->db->exists('utility_readings', 'room_id = ? AND month = ? AND year = ?', [
            $data['room_id'], $data['month'], $data['year']
        ]);
        if ($exists) {
            $errors['room_id'][] = 'Chỉ số điện nước phòng này trong tháng đã tồn tại.';
        }

        if (!empty($errors)) {
            $this->withOldInput($data);
            $this->withErrors($errors, '/admin/utilities/create');
            return;
        }

        try {
            $this->db->insert('utility_readings', [
                'room_id'     => (int)$data['room_id'],
                'month'       => (int)$data['month'],
                'year'        => (int)$data['year'],
                'elec_prev'   => (float)$data['elec_prev'],
                'elec_curr'   => (float)$data['elec_curr'],
                'water_prev'  => (float)$data['water_prev'],
                'water_curr'  => (float)$data['water_curr'],
                'elec_rate'   => (float)($data['elec_rate'] ?: 3500.00),
                'water_rate'  => (float)($data['water_rate'] ?: 15000.00),
                'recorded_by' => $this->auth('id'),
                'notes'       => $data['notes']
            ]);

            $this->flash('success', 'Ghi chỉ số điện nước thành công.');
            $this->redirect('/admin/utilities');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->flash('error', 'Lỗi khi ghi chỉ số điện nước.');
            $this->back();
        }
    }

    public function edit(array $params = []): void
    {
        $id = (int)$params['id'];
        $reading = $this->db->selectOne("
            SELECT u.*, r.room_number, b.name AS building_name
            FROM utility_readings u
            JOIN rooms r ON r.id = u.room_id
            JOIN buildings b ON b.id = r.building_id
            WHERE u.id = ?
        ", [$id]);

        if (!$reading) {
            $this->abort(404, 'Không tìm thấy bản ghi');
        }

        $this->view('admin/utilities/edit', [
            'title'   => 'Chỉnh sửa chỉ số điện nước',
            'reading' => $reading
        ]);
    }

    public function update(array $params = []): void
    {
        $this->verifyCsrf();
        $id = (int)$params['id'];
        $reading = $this->db->selectOne("SELECT * FROM utility_readings WHERE id = ?", [$id]);

        if (!$reading) {
            $this->abort(404, 'Không tìm thấy bản ghi');
        }

        $data = $this->only([
            'elec_prev', 'elec_curr', 
            'water_prev', 'water_curr', 
            'elec_rate', 'water_rate', 
            'notes'
        ]);

        $errors = $this->validate($data, [
            'elec_prev'  => 'required|numeric|min:0',
            'elec_curr'  => 'required|numeric|min:0',
            'water_prev' => 'required|numeric|min:0',
            'water_curr' => 'required|numeric|min:0'
        ]);

        if ((float)$data['elec_curr'] < (float)$data['elec_prev']) {
            $errors['elec_curr'][] = 'Chỉ số điện cuối kỳ không thể nhỏ hơn đầu kỳ.';
        }
        if ((float)$data['water_curr'] < (float)$data['water_prev']) {
            $errors['water_curr'][] = 'Chỉ số nước cuối kỳ không thể nhỏ hơn đầu kỳ.';
        }

        if (!empty($errors)) {
            $this->withOldInput($data);
            $this->withErrors($errors, "/admin/utilities/{$id}/edit");
            return;
        }

        try {
            $this->db->update('utility_readings', [
                'elec_prev'  => (float)$data['elec_prev'],
                'elec_curr'  => (float)$data['elec_curr'],
                'water_prev' => (float)$data['water_prev'],
                'water_curr' => (float)$data['water_curr'],
                'elec_rate'  => (float)($data['elec_rate'] ?: 3500.00),
                'water_rate' => (float)($data['water_rate'] ?: 15000.00),
                'notes'      => $data['notes']
            ], 'id = ?', [$id]);

            $this->flash('success', 'Cập nhật chỉ số điện nước thành công.');
            $this->redirect('/admin/utilities');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->flash('error', 'Lỗi khi cập nhật chỉ số điện nước.');
            $this->back();
        }
    }
}
