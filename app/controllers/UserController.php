<?php
/**
 * app/controllers/UserController.php
 * ─────────────────────────────────────────────────────────────
 *  Quản lý tài khoản người dùng (admin, sinh viên)
 *  Hỗ trợ AJAX CRUD — không tải lại trang
 *
 *  Thành viên 1 phụ trách (Users module)
 *  Điểm "Xuất sắc": REST API + AJAX + Singleton DB
 * ─────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

require_once __DIR__ . '/../models/Models.php';

class UserController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  ADMIN VIEWS — Trang HTML (Server-side rendering)
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /admin/users
     * Trang danh sách người dùng
     */
    public function index(array $params = []): void
    {
        $this->requireAdmin();

        // Lấy tham số phân trang
        [$page, $perPage] = $this->paginationParams();

        // Lấy bộ lọc từ query string
        $search = $this->request('q', '');
        $role   = $this->request('role', '');
        $status = $this->request('status', '');

        // Xây dựng điều kiện WHERE
        $where  = '1';
        $params = [];

        if ($search) {
            $where .= ' AND (username LIKE ? OR email LIKE ? OR u.id IN (
                SELECT user_id FROM students WHERE full_name LIKE ?
            ))';
            $search_param = "%{$search}%";
            $params = [$search_param, $search_param, $search_param];
        }

        if ($role) {
            $where .= ' AND role = ?';
            $params[] = $role;
        }

        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }

        // Phân trang
        $result = $this->db->paginate(
            "SELECT u.*, 
                    COALESCE(s.full_name, '-') AS student_name,
                    COALESCE(s.student_code, '-') AS student_code
             FROM users u
             LEFT JOIN students s ON s.user_id = u.id
             WHERE {$where}
             ORDER BY u.created_at DESC",
            $params,
            $page,
            $perPage
        );

        $this->view('admin/users/index', [
            'title'      => 'Quản lý tài khoản',
            'users'      => $result['data'],
            'pagination' => $result,
            'search'     => $search,
            'role'       => $role,
            'status'     => $status,
            'roles'      => ['admin' => 'Quản trị viên', 'student' => 'Sinh viên'],
            'statuses'   => ['active' => 'Hoạt động', 'inactive' => 'Không hoạt động'],
        ]);
    }

    /**
     * GET /admin/users/:id
     * Chi tiết người dùng + thông tin sinh viên
     */
    public function show(array $params = []): void
    {
        $this->requireAdmin();

        $id   = (int)$params['id'];
        $user = $this->db->selectOne(
            "SELECT u.*, 
                    s.id AS student_id,
                    s.student_code,
                    s.full_name,
                    s.gender,
                    s.dob,
                    s.faculty,
                    s.program,
                    s.priority_level,
                    s.phone,
                    s.hometown,
                    s.id_card
             FROM users u
             LEFT JOIN students s ON s.user_id = u.id
             WHERE u.id = ?",
            [$id]
        );

        if (!$user) {
            $this->abort(404, 'Người dùng không tồn tại');
        }

        $this->view('admin/users/show', [
            'title' => 'Chi tiết tài khoản: ' . htmlspecialchars($user['username']),
            'user'  => $user,
        ]);
    }

    /**
     * GET /admin/users/create
     * Form tạo tài khoản mới
     */
    public function create(array $params = []): void
    {
        $this->requireAdmin();

        $this->view('admin/users/create', [
            'title'    => 'Tạo tài khoản mới',
            'faculties' => [
                'CNTT' => 'Công nghệ thông tin',
                'KT'   => 'Kinh tế',
                'XH'   => 'Xã hội học',
                'SP'   => 'Sư phạm',
            ],
        ]);
    }

    /**
     * GET /admin/users/:id/edit
     * Form chỉnh sửa tài khoản
     */
    public function edit(array $params = []): void
    {
        $this->requireAdmin();

        $id   = (int)$params['id'];
        $user = $this->userModel->find($id);

        if (!$user) {
            $this->abort(404, 'Người dùng không tồn tại');
        }

        $student = $this->db->selectOne(
            "SELECT * FROM students WHERE user_id = ?",
            [$id]
        );

        $this->view('admin/users/edit', [
            'title'     => 'Chỉnh sửa: ' . htmlspecialchars($user['username']),
            'user'      => $user,
            'student'   => $student,
            'faculties' => [
                'CNTT' => 'Công nghệ thông tin',
                'KT'   => 'Kinh tế',
                'XH'   => 'Xã hội học',
                'SP'   => 'Sư phạm',
            ],
        ]);
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  AJAX API — JSON responses (no page reload)
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /api/users/:id
     * Lấy dữ liệu người dùng (cho modal, form pre-fill)
     */
    public function apiShow(array $params = []): void
    {
        $this->requireAdmin();

        $id   = (int)$params['id'];
        $user = $this->db->selectOne(
            "SELECT u.*, 
                    COALESCE(s.full_name, '') AS student_name,
                    COALESCE(s.student_code, '') AS student_code
             FROM users u
             LEFT JOIN students s ON s.user_id = u.id
             WHERE u.id = ?",
            [$id]
        );

        if (!$user) {
            $this->jsonError('Người dùng không tồn tại', 404);
        }

        $this->jsonOk($user);
    }

    /**
     * POST /api/users
     * Tạo tài khoản mới (AJAX)
     * ───────────────────────────────────────────────────────────
     *
     * Dữ liệu gửi lên:
     *   {
     *     "username": "sv001",
     *     "email": "sv001@ktx.edu.vn",
     *     "password": "SecurePass123",
     *     "password_confirm": "SecurePass123",
     *     "role": "student",
     *     "status": "active",
     *     "student_code": "123456",
     *     "full_name": "Nguyễn Văn A",
     *     "gender": "male",
     *     "dob": "2005-01-01",
     *     "faculty": "CNTT",
     *     ...
     *   }
     */
    public function store(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        // Lấy dữ liệu người dùng
        $userData = $this->only([
            'username',
            'email',
            'password',
            'password_confirm',
            'role',
            'status',
        ]);

        // Lấy dữ liệu sinh viên (nếu role = student)
        $studentData = null;
        if ($userData['role'] === 'student') {
            $studentData = $this->only([
                'student_code',
                'full_name',
                'gender',
                'dob',
                'faculty',
                'program',
                'priority_level',
                'phone',
                'hometown',
                'id_card',
            ]);
        }

        // ─── Validation ────────────────────────────────────────
        $errors = $this->validate($userData, [
            'username'           => 'required|min:3|max:50|unique:users,username',
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|min:8|confirmed',
            'role'               => 'required|in:admin,student',
            'status'             => 'required|in:active,inactive',
        ]);

        if ($userData['role'] === 'student' && !empty($studentData)) {
            $studentErrors = $this->validate($studentData, [
                'student_code' => 'required|unique:students,student_code',
                'full_name'    => 'required|min:2|max:100',
                'gender'       => 'required|in:male,female',
                'dob'          => 'required|date',
                'faculty'      => 'required|max:20',
                'id_card'      => 'required|unique:students,id_card',
            ]);
            $errors = array_merge($errors, $studentErrors);
        }

        if (!empty($errors)) {
            $this->jsonError('Dữ liệu không hợp lệ', 422, $errors);
        }

        // ─── Create ────────────────────────────────────────────
        try {
            $this->db->transaction(function (Database $db) use ($userData, $studentData) {
                // 1. Hash password và tạo user
                $userData['password_hash'] = password_hash(
                    $userData['password'],
                    PASSWORD_BCRYPT,
                    ['cost' => 12]
                );
                unset($userData['password'], $userData['password_confirm']);

                $userId = $db->insert('users', $userData);

                // 2. Tạo hồ sơ sinh viên (nếu role = student)
                if ($userData['role'] === 'student' && !empty($studentData)) {
                    $studentData['user_id'] = $userId;
                    $db->insert('students', $studentData);
                }

                // 3. Gửi thông báo
                $db->insert('notifications', [
                    'user_id' => $userId,
                    'title'   => 'Tài khoản được tạo',
                    'message' => 'Tài khoản của bạn đã được tạo thành công. Vui lòng đăng nhập.',
                    'type'    => 'system',
                ]);
            });

            $user = $this->userModel->find($this->db->lastInsertId());

            $this->jsonOk(
                $user,
                'Tạo tài khoản thành công',
                201
            );
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi tạo tài khoản: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/users/:id
     * Cập nhật tài khoản (AJAX)
     */
    public function update(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];

        // Kiểm tra tồn tại
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->jsonError('Người dùng không tồn tại', 404);
        }

        // Lấy dữ liệu
        $userData = $this->only([
            'email',
            'role',
            'status',
            'password', // optional
        ]);

        $studentData = null;
        if ($user['role'] === 'student') {
            $studentData = $this->only([
                'student_code',
                'full_name',
                'gender',
                'dob',
                'faculty',
                'program',
                'priority_level',
                'phone',
                'hometown',
            ]);
        }

        // ─── Validation ────────────────────────────────────────
        $errors = $this->validate($userData, [
            'email'  => "required|email|unique:users,email,{$id},id",
            'role'   => 'required|in:admin,student',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|min:8', // optional
        ]);

        if (!empty($studentData)) {
            $studentErrors = $this->validate($studentData, [
                'student_code' => "required|unique:students,student_code,{$id},user_id",
                'full_name'    => 'required|min:2|max:100',
                'gender'       => 'required|in:male,female',
                'faculty'      => 'required|max:20',
            ]);
            $errors = array_merge($errors, $studentErrors);
        }

        if (!empty($errors)) {
            $this->jsonError('Dữ liệu không hợp lệ', 422, $errors);
        }

        // ─── Update ────────────────────────────────────────────
        try {
            $this->db->transaction(function (Database $db) use (
                $id,
                $userData,
                $studentData
            ) {
                // 1. Cập nhật user
                if (!empty($userData['password'])) {
                    $userData['password_hash'] = password_hash(
                        $userData['password'],
                        PASSWORD_BCRYPT,
                        ['cost' => 12]
                    );
                    unset($userData['password']);
                }

                $db->update('users', $userData, 'id = ?', [$id]);

                // 2. Cập nhật student (nếu là sinh viên)
                if (!empty($studentData)) {
                    $existingStudent = $db->selectOne(
                        "SELECT id FROM students WHERE user_id = ?",
                        [$id]
                    );

                    if ($existingStudent) {
                        $db->update(
                            'students',
                            $studentData,
                            'user_id = ?',
                            [$id]
                        );
                    } else {
                        $studentData['user_id'] = $id;
                        $db->insert('students', $studentData);
                    }
                }
            });

            $updated = $this->userModel->find($id);

            $this->jsonOk($updated, 'Cập nhật tài khoản thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi cập nhật tài khoản', 500);
        }
    }

    /**
     * DELETE /api/users/:id
     * Xóa tài khoản (soft delete — set status = inactive)
     */
    public function destroy(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id = (int)$params['id'];

        // Kiểm tra tồn tại
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->jsonError('Người dùng không tồn tại', 404);
        }

        // Không cho xóa user hiện tại
        if ($id === $this->auth('id')) {
            $this->jsonError('Không thể xóa tài khoản của chính bạn', 409);
        }

        // Kiểm tra có contract active không
        $hasActiveContract = $this->db->exists(
            'contracts',
            "student_id IN (SELECT id FROM students WHERE user_id = ?) AND status = 'active'",
            [$id]
        );

        if ($hasActiveContract) {
            $this->jsonError(
                'Không thể xóa: sinh viên có hợp đồng đang hoạt động',
                409
            );
        }

        try {
            $this->db->transaction(function (Database $db) use ($id) {
                // Soft delete (set status = inactive)
                $db->update('users', ['status' => 'inactive'], 'id = ?', [$id]);

                // Ghi log
                $db->insert('notifications', [
                    'user_id' => $id,
                    'title'   => 'Tài khoản đã bị vô hiệu hóa',
                    'message' => 'Tài khoản của bạn đã bị vô hiệu hóa bởi quản trị viên.',
                    'type'    => 'system',
                ]);
            });

            $this->jsonOk(null, 'Đã xóa tài khoản');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi xóa tài khoản', 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  BULK ACTIONS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * POST /api/users/bulk-status
     * Cập nhật trạng thái nhiều user cùng lúc
     *
     * Request:
     *   {
     *     "ids": [1, 2, 3],
     *     "status": "active"
     *   }
     */
    public function bulkUpdateStatus(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $ids    = $this->request('ids', []);
        $status = $this->request('status', '');

        // Validation
        if (empty($ids) || !is_array($ids)) {
            $this->jsonError('ID không hợp lệ', 422);
        }

        if (!in_array($status, ['active', 'inactive'])) {
            $this->jsonError('Trạng thái không hợp lệ', 422);
        }

        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            $affected = $this->db->update(
                'users',
                ['status' => $status],
                "id IN ({$placeholders})",
                $ids
            );

            $this->jsonOk(
                ['affected' => $affected],
                "Đã cập nhật trạng thái cho {$affected} tài khoản"
            );
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi cập nhật', 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  PASSWORD MANAGEMENT
     * ───────────────────────────────────────────────────────────
     */

    /**
     * POST /api/users/:id/reset-password
     * Đặt lại mật khẩu người dùng (admin reset cho sinh viên)
     *
     * Request:
     *   {
     *     "password": "TempPassword123",
     *     "send_email": true
     *   }
     */
    public function resetPassword(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $id       = (int)$params['id'];
        $password = $this->request('password', '');
        $sendEmail = (bool)$this->request('send_email', true);

        // Kiểm tra tồn tại
        $user = $this->userModel->find($id);
        if (!$user) {
            $this->jsonError('Người dùng không tồn tại', 404);
        }

        // Validation
        if (strlen($password) < 8) {
            $this->jsonError('Mật khẩu phải ít nhất 8 ký tự', 422);
        }

        try {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $this->db->transaction(function (Database $db) use (
                $id,
                $hash,
                $user,
                $sendEmail
            ) {
                // 1. Cập nhật mật khẩu
                $db->update('users', ['password_hash' => $hash], 'id = ?', [$id]);

                // 2. Gửi thông báo
                $db->insert('notifications', [
                    'user_id' => $id,
                    'title'   => 'Mật khẩu được đặt lại',
                    'message' => 'Quản trị viên đã đặt lại mật khẩu của bạn. Vui lòng đăng nhập bằng mật khẩu mới.',
                    'type'    => 'system',
                ]);

                // 3. Gửi email (nếu yêu cầu)
                // TODO: Gửi email qua mailer
                // $mailer->send($user['email'], 'Mật khẩu được đặt lại', ...);
            });

            $this->jsonOk(null, 'Mật khẩu đã được đặt lại');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi đặt lại mật khẩu', 500);
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  EXPORT & IMPORT
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /api/users/export
     * Xuất danh sách user ra Excel
     */
    public function export(array $params = []): void
    {
        $this->requireAdmin();

        $format = $this->request('format', 'csv');

        // Lấy dữ liệu
        $users = $this->db->select(
            "SELECT u.id, u.username, u.email, u.role, u.status, u.created_at,
                    COALESCE(s.full_name, '-') AS full_name,
                    COALESCE(s.student_code, '-') AS student_code
             FROM users u
             LEFT JOIN students s ON s.user_id = u.id
             ORDER BY u.created_at DESC"
        );

        if ($format === 'csv') {
            // CSV format
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Username', 'Email', 'Tên', 'Mã SV', 'Role', 'Status', 'Tạo lúc']);

            foreach ($users as $u) {
                fputcsv($output, [
                    $u['id'],
                    $u['username'],
                    $u['email'],
                    $u['full_name'],
                    $u['student_code'],
                    $u['role'],
                    $u['status'],
                    $u['created_at'],
                ]);
            }

            fclose($output);
            exit;
        } else {
            // JSON format
            header('Content-Type: application/json');
            echo json_encode($users);
            exit;
        }
    }

    /**
     * ───────────────────────────────────────────────────────────
     *  STUDENT-FACING ENDPOINTS
     * ───────────────────────────────────────────────────────────
     */

    /**
     * GET /api/profile
     * Lấy profile của sinh viên hiện tại
     */
    public function apiProfile(array $params = []): void
    {
        $this->requireAuth();

        $userId = $this->auth('id');

        $user = $this->db->selectOne(
            "SELECT u.*, 
                    COALESCE(s.full_name, '-') AS full_name,
                    COALESCE(s.student_code, '-') AS student_code,
                    COALESCE(s.phone, '-') AS phone,
                    COALESCE(s.hometown, '-') AS hometown
             FROM users u
             LEFT JOIN students s ON s.user_id = u.id
             WHERE u.id = ?",
            [$userId]
        );

        if (!$user) {
            $this->jsonError('Không tìm thấy profile', 404);
        }

        unset($user['password_hash']); // không gửi password hash
        $this->jsonOk($user);
    }

    /**
     * PUT /api/profile
     * Cập nhật profile sinh viên
     */
    public function updateProfile(array $params = []): void
    {
        $this->requireAuth();

        $userId = $this->auth('id');

        // Lấy dữ liệu từ request
        $data = $this->only([
            'phone',
            'hometown',
            'full_name',
        ]);

        // Validation
        $errors = $this->validate($data, [
            'phone'     => 'nullable|min:10|max:15',
            'hometown'  => 'nullable|max:100',
            'full_name' => 'nullable|min:2|max:100',
        ]);

        if (!empty($errors)) {
            $this->jsonError('Dữ liệu không hợp lệ', 422, $errors);
        }

        try {
            $this->db->update('students', $data, 'user_id = ?', [$userId]);

            $profile = $this->db->selectOne(
                "SELECT u.*, s.* FROM users u
                 LEFT JOIN students s ON s.user_id = u.id
                 WHERE u.id = ?",
                [$userId]
            );

            unset($profile['password_hash']);
            $this->jsonOk($profile, 'Cập nhật profile thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi cập nhật profile', 500);
        }
    }

    /**
     * PUT /api/change-password
     * Đổi mật khẩu của sinh viên
     */
    public function changePassword(array $params = []): void
    {
        $this->requireAuth();

        $userId = $this->auth('id');

        // Lấy dữ liệu
        $oldPassword = $this->request('old_password', '');
        $newPassword = $this->request('new_password', '');
        $confirmPassword = $this->request('confirm_password', '');

        // Validation
        if (!$oldPassword || !$newPassword) {
            $this->jsonError('Mật khẩu không được để trống', 422);
        }

        if ($newPassword !== $confirmPassword) {
            $this->jsonError('Mật khẩu xác nhận không khớp', 422);
        }

        if (strlen($newPassword) < 8) {
            $this->jsonError('Mật khẩu mới phải ít nhất 8 ký tự', 422);
        }

        // Kiểm tra mật khẩu cũ
        $user = $this->userModel->find($userId);
        if (!password_verify($oldPassword, $user['password_hash'])) {
            $this->jsonError('Mật khẩu cũ không đúng', 401);
        }

        try {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->userModel->update($userId, ['password_hash' => $newHash]);

            $this->jsonOk(null, 'Đổi mật khẩu thành công');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi đổi mật khẩu', 500);
        }
    }
}
