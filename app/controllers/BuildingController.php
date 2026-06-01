<?php
/**
 * app/controllers/BuildingController.php
 * Admin Building Management Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class BuildingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function index(array $params = []): void
    {
        // Lấy danh sách tòa nhà, kèm theo số lượng phòng
        $buildings = $this->db->select("
            SELECT b.*, 
                   (SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.id) AS room_count,
                   (SELECT SUM(capacity) FROM rooms r WHERE r.building_id = b.id) AS total_capacity,
                   (SELECT SUM(current_occupants) FROM rooms r WHERE r.building_id = b.id) AS total_occupants
            FROM buildings b
            ORDER BY b.name
        ");

        $this->view('admin/buildings/index', [
            'title'     => 'Quản lý tòa nhà',
            'buildings' => $buildings
        ]);
    }

    public function show(array $params = []): void
    {
        $id = (int)$params['id'];
        $building = $this->db->selectOne("SELECT * FROM buildings WHERE id = ?", [$id]);

        if (!$building) {
            $this->abort(404, 'Không tìm thấy tòa nhà.');
        }

        // Lấy danh sách phòng thuộc tòa nhà
        $rooms = $this->db->select("
            SELECT r.*, 
                   (SELECT COUNT(*) FROM contracts c WHERE c.room_id = r.id AND c.status = 'active') AS active_contracts
            FROM rooms r
            WHERE r.building_id = ?
            ORDER BY r.room_number
        ", [$id]);

        $this->view('admin/buildings/show', [
            'title'    => 'Chi tiết tòa nhà: ' . htmlspecialchars($building['name']),
            'building' => $building,
            'rooms'    => $rooms
        ]);
    }

    public function create(array $params = []): void
    {
        $this->view('admin/buildings/create', [
            'title' => 'Thêm tòa nhà mới'
        ]);
    }

    public function store(array $params = []): void
    {
        $this->verifyCsrf();
        $data = $this->only(['name', 'total_floors', 'gender_type', 'manager_name', 'manager_phone', 'address']);

        $errors = $this->validate($data, [
            'name'          => 'required|max:100',
            'total_floors'  => 'required|integer|min:1',
            'gender_type'   => 'required|in:male,female,mixed',
            'manager_name'  => 'required|max:100',
            'manager_phone' => 'required|max:15',
            'address'       => 'required|max:255'
        ]);

        if (!empty($errors)) {
            $this->withOldInput($data);
            $this->withErrors($errors, '/admin/buildings/create');
            return;
        }

        try {
            $this->db->insert('buildings', $data);
            $this->flash('success', "Tòa nhà {$data['name']} đã được tạo thành công.");
            $this->redirect('/admin/buildings');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->flash('error', 'Lỗi khi tạo tòa nhà.');
            $this->back();
        }
    }

    public function edit(array $params = []): void
    {
        $id = (int)$params['id'];
        $building = $this->db->selectOne("SELECT * FROM buildings WHERE id = ?", [$id]);

        if (!$building) {
            $this->abort(404, 'Không tìm thấy tòa nhà.');
        }

        $this->view('admin/buildings/edit', [
            'title'    => 'Chỉnh sửa tòa nhà: ' . htmlspecialchars($building['name']),
            'building' => $building
        ]);
    }

    public function update(array $params = []): void
    {
        $this->verifyCsrf();
        $id = (int)$params['id'];
        $building = $this->db->selectOne("SELECT * FROM buildings WHERE id = ?", [$id]);

        if (!$building) {
            $this->abort(404, 'Không tìm thấy tòa nhà.');
        }

        $data = $this->only(['name', 'total_floors', 'gender_type', 'manager_name', 'manager_phone', 'address']);

        $errors = $this->validate($data, [
            'name'          => 'required|max:100',
            'total_floors'  => 'required|integer|min:1',
            'gender_type'   => 'required|in:male,female,mixed',
            'manager_name'  => 'required|max:100',
            'manager_phone' => 'required|max:15',
            'address'       => 'required|max:255'
        ]);

        if (!empty($errors)) {
            $this->withOldInput($data);
            $this->withErrors($errors, "/admin/buildings/{$id}/edit");
            return;
        }

        try {
            $this->db->update('buildings', $data, 'id = ?', [$id]);
            $this->flash('success', "Cập nhật tòa nhà {$data['name']} thành công.");
            $this->redirect('/admin/buildings');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->flash('error', 'Lỗi khi cập nhật tòa nhà.');
            $this->back();
        }
    }

    public function destroy(array $params = []): void
    {
        $id = (int)$params['id'];

        // Kiểm tra xem tòa nhà có chứa phòng không
        $hasRooms = $this->db->exists('rooms', 'building_id = ?', [$id]);
        if ($hasRooms) {
            $this->jsonError('Không thể xóa tòa nhà đang có chứa phòng ở.', 409);
        }

        try {
            $this->db->delete('buildings', 'id = ?', [$id]);
            $this->jsonOk(null, 'Xóa tòa nhà thành công.');
        } catch (\Throwable $e) {
            $this->jsonError('Lỗi khi xóa tòa nhà.', 500);
        }
    }
}
