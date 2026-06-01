<?php
/**
 * app/controllers/RoomController.php
 * ─────────────────────────────────────────────────────────────
 *  Controller mẫu đầy đủ — Thành viên 2 phụ trách
 *  Thể hiện cách dùng BaseController trong thực tế
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/Models.php';

class RoomController extends BaseController
{
    private RoomModel     $roomModel;
    private BuildingModel $buildingModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();   // chỉ admin vào được
        $this->roomModel     = new RoomModel();
        $this->buildingModel = new BuildingModel();
    }


    // ─────────────────────────────────────────────────────────
    //  GET /admin/rooms
    // ─────────────────────────────────────────────────────────
    public function index(array $params = []): void
    {
        [$page, $perPage] = $this->paginationParams();

        $search = $this->request('q', '');
        $status = $this->request('status', '');
        $buildingId = (int)$this->request('building_id', 0);

        // Xây WHERE động
        $where  = '1';
        $args   = [];
        if ($search) {
            $where .= ' AND r.room_number LIKE ?';
            $args[] = "%{$search}%";
        }
        if ($status) {
            $where .= ' AND r.status = ?';
            $args[] = $status;
        }
        if ($buildingId > 0) {
            $where .= ' AND r.building_id = ?';
            $args[] = $buildingId;
        }

        $result = $this->db->paginate(
            "SELECT r.*, b.name AS building_name
             FROM rooms r
             JOIN buildings b ON b.id = r.building_id
             WHERE {$where}
             ORDER BY b.name, r.room_number",
            $args, $page, $perPage
        );

        $this->view('admin/rooms/index', [
            'title'       => 'Quản lý phòng KTX',
            'rooms'       => $result['data'],
            'pagination'  => $result,
            'buildings'   => $this->buildingModel->active(),
            'search'      => $search,
            'filterStatus'=> $status,
        ]);
    }


    // ─────────────────────────────────────────────────────────
    //  GET /admin/rooms/create
    // ─────────────────────────────────────────────────────────
    public function create(array $params = []): void
    {
        $this->view('admin/rooms/create', [
            'title'     => 'Thêm phòng mới',
            'buildings' => $this->buildingModel->active(),
        ]);
    }


    // ─────────────────────────────────────────────────────────
    //  POST /admin/rooms
    // ─────────────────────────────────────────────────────────
    public function store(array $params = []): void
    {
        $this->verifyCsrf();

        $data = $this->only([
            'building_id', 'room_number', 'floor',
            'room_type', 'capacity', 'price_per_month', 'has_ac',
        ]);

        $errors = $this->validate($data, [
            'building_id'     => 'required|integer',
            'room_number'     => 'required|max:10',
            'floor'           => 'required|integer|min:1|max:50',
            'room_type'       => 'required|in:standard,deluxe,ac_standard,ac_deluxe',
            'capacity'        => 'required|integer|min:1|max:20',
            'price_per_month' => 'required|numeric|min:100000',
        ]);

        // Kiểm tra số phòng trùng trong tòa
        if (empty($errors['room_number'])) {
            $exists = $this->db->exists(
                'rooms',
                'building_id = ? AND room_number = ?',
                [$data['building_id'], $data['room_number']]
            );
            if ($exists) {
                $errors['room_number'][] = 'Số phòng đã tồn tại trong tòa này.';
            }
        }

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->jsonError('Dữ liệu không hợp lệ.', 422, $errors);
            }
            $this->withOldInput($data);
            $this->withErrors($errors, '/admin/rooms/create');
        }

        $data['has_ac']  = isset($data['has_ac']) && $data['has_ac'] ? 1 : 0;
        $data['status']  = 'available';

        try {
            $id = $this->roomModel->create($data);

            if ($this->isAjax()) {
                $this->jsonOk(
                    $this->roomModel->find($id),
                    'Tạo phòng thành công.',
                    201
                );
            }

            $this->flash('success', "Phòng {$data['room_number']} đã được tạo.");
            $this->redirect('/admin/rooms');

        } catch (\Throwable $e) {
            if ($this->isAjax()) {
                $this->jsonError('Lỗi khi tạo phòng: ' . $e->getMessage(), 500);
            }
            $this->flash('error', 'Đã xảy ra lỗi. Vui lòng thử lại.');
            $this->back();
        }
    }


    // ─────────────────────────────────────────────────────────
    //  GET /admin/rooms/:id
    // ─────────────────────────────────────────────────────────
    public function show(array $params = []): void
    {
        $id   = (int)$this->param($params, 'id');
        $room = $this->db->selectOne("
            SELECT r.*, b.name AS building_name, b.gender_type
            FROM rooms r
            JOIN buildings b ON b.id = r.building_id
            WHERE r.id = ?
        ", [$id]);

        if (!$room) {
            $this->abort(404, 'Phòng không tồn tại.');
        }

        // Lấy sinh viên đang ở trong phòng
        $occupants = $this->db->select("
            SELECT s.full_name, s.student_code, s.gender,
                   c.start_date, c.end_date, c.status AS contract_status
            FROM contracts c
            JOIN students s ON s.id = c.student_id
            WHERE c.room_id = ? AND c.status = 'active'
        ", [$id]);

        // Amenities
        $amenities = $this->db->select(
            "SELECT * FROM room_amenities WHERE room_id = ? ORDER BY amenity_name",
            [$id]
        );

        if ($this->isAjax()) {
            $this->jsonOk([
                'room'      => $room,
                'occupants' => $occupants,
                'amenities' => $amenities,
            ]);
        }

        $this->view('admin/rooms/show', [
            'title'     => "Phòng {$room['room_number']}",
            'room'      => $room,
            'occupants' => $occupants,
            'amenities' => $amenities,
        ]);
    }


    // ─────────────────────────────────────────────────────────
    //  GET /admin/rooms/:id/edit
    // ─────────────────────────────────────────────────────────
    public function edit(array $params = []): void
    {
        $id   = (int)$this->param($params, 'id');
        $room = $this->roomModel->find($id);

        if (!$room) $this->abort(404, 'Phòng không tồn tại.');

        $this->view('admin/rooms/edit', [
            'title'     => "Chỉnh sửa phòng {$room['room_number']}",
            'room'      => $room,
            'buildings' => $this->buildingModel->active(),
        ]);
    }


    // ─────────────────────────────────────────────────────────
    //  PUT /admin/rooms/:id
    // ─────────────────────────────────────────────────────────
    public function update(array $params = []): void
    {
        if (!$this->isAjax()) $this->verifyCsrf();

        $id   = (int)$this->param($params, 'id');
        $room = $this->roomModel->find($id);

        if (!$room) $this->jsonError('Phòng không tồn tại.', 404);

        $data = $this->only([
            'room_number', 'floor', 'room_type',
            'capacity', 'price_per_month', 'has_ac', 'status',
        ]);

        $errors = $this->validate($data, [
            'room_number'     => 'required|max:10',
            'floor'           => 'required|integer|min:1',
            'room_type'       => 'required|in:standard,deluxe,ac_standard,ac_deluxe',
            'capacity'        => 'required|integer|min:1|max:20',
            'price_per_month' => 'required|numeric|min:100000',
            'status'          => 'required|in:available,full,maintenance,inactive',
        ]);

        if (!empty($errors)) {
            $this->isAjax()
                ? $this->jsonError('Dữ liệu không hợp lệ.', 422, $errors)
                : $this->withErrors($errors, "/admin/rooms/{$id}/edit");
        }

        $data['has_ac'] = !empty($data['has_ac']) ? 1 : 0;

        try {
            $this->roomModel->update($id, $data);

            if ($this->isAjax()) {
                $this->jsonOk($this->roomModel->find($id), 'Cập nhật phòng thành công.');
            }

            $this->flash('success', 'Cập nhật phòng thành công.');
            $this->redirect('/admin/rooms');

        } catch (\Throwable $e) {
            $this->isAjax()
                ? $this->jsonError('Lỗi khi cập nhật.', 500)
                : $this->abort(500);
        }
    }


    // ─────────────────────────────────────────────────────────
    //  DELETE /admin/rooms/:id
    // ─────────────────────────────────────────────────────────
    public function destroy(array $params = []): void
    {
        $id   = (int)$this->param($params, 'id');
        $room = $this->roomModel->find($id);

        if (!$room) {
            $this->jsonError('Phòng không tồn tại.', 404);
        }

        // Không xóa phòng đang có sinh viên
        $hasActive = $this->db->exists(
            'contracts',
            "room_id = ? AND status = 'active'",
            [$id]
        );

        if ($hasActive) {
            $this->jsonError('Không thể xóa phòng đang có sinh viên ở.', 409);
        }

        try {
            $this->roomModel->delete($id);
            $this->jsonOk(null, 'Xóa phòng thành công.');
        } catch (\Throwable $e) {
            $this->jsonError('Lỗi khi xóa phòng.', 500);
        }
    }


    // ─────────────────────────────────────────────────────────
    //  GET /admin/rooms/:id/amenities  (AJAX)
    // ─────────────────────────────────────────────────────────
    public function amenities(array $params = []): void
    {
        $id        = (int)$this->param($params, 'id');
        $amenities = $this->db->select(
            "SELECT * FROM room_amenities WHERE room_id = ? ORDER BY amenity_name",
            [$id]
        );
        $this->jsonOk($amenities, 'Lấy danh sách trang thiết bị thành công.');
    }
}


// ─────────────────────────────────────────────────────────────
/**
 * app/controllers/AuthController.php — mẫu auth controller
 */
class AuthController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function showLogin(array $params = []): void
    {
        $this->view('auth/login', ['title' => 'Đăng nhập'], false);
    }

    public function login(array $params = []): void
    {
        $this->verifyCsrf();

        $email    = $this->request('email');
        $password = $this->request('password', '');

        $errors = $this->validate(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required']
        );

        if (!empty($errors)) {
            $this->withOldInput(['email' => $email]);
            $this->withErrors($errors, '/auth/login');
        }

        // Xác thực
        $user = $this->db->selectOne(
            "SELECT id, username, email, password_hash, role, status FROM users WHERE email = ?",
            [$email]
        );

        if (!$user
            || $user['status'] !== 'active'
            || !password_verify($password, $user['password_hash'])
        ) {
            $this->withOldInput(['email' => $email]);
            $_SESSION['_errors']['general'][] = 'Email hoặc mật khẩu không đúng.';
            $this->redirect('/auth/login');
        }

        unset($user['password_hash']);
        $this->loginUser($user);

        // Redirect về trang định đến (nếu có)
        $intended = $_SESSION['_intended_url'] ?? null;
        unset($_SESSION['_intended_url']);

        $home = $user['role'] === 'admin' ? '/admin/dashboard' : '/student/dashboard';
        $this->redirect($intended ?? $home);
    }

    public function showRegister(array $params = []): void
    {
        $this->view('auth/register', ['title' => 'Đăng ký'], false);
    }

    public function register(array $params = []): void
    {
        $this->verifyCsrf();

        $data = $this->only(['username', 'email', 'password', 'password_confirm']);

        $errors = $this->validate($data, [
            'username'         => 'required|min:3|max:50|unique:users,username',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required|min:8',
            'password_confirm' => 'required|confirmed',
        ]);

        // Fix: truyền đúng tham số cho confirmed
        if (($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
            $errors['password_confirm'][] = 'Xác nhận mật khẩu không khớp.';
        }

        if (!empty($errors)) {
            $this->withOldInput(['username' => $data['username'], 'email' => $data['email']]);
            $this->withErrors($errors, '/auth/register');
        }

        try {
            $hash   = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $userId = $this->db->insert('users', [
                'username'      => $data['username'],
                'email'         => $data['email'],
                'password_hash' => $hash,
                'role'          => 'student',
            ]);

            $this->flash('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
            $this->redirect('/auth/login');

        } catch (\Throwable $e) {
            $this->flash('error', 'Đã xảy ra lỗi. Vui lòng thử lại.');
            $this->back();
        }
    }

    public function logout(array $params = []): void
    {
        $this->logoutUser();
        $this->flash('success', 'Đã đăng xuất thành công.');
        $this->redirect('/auth/login');
    }
}
