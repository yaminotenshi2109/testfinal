<?php
/**
 * app/controllers/NotificationController.php
 * Student and Admin Notifications Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class NotificationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    /**
     * GET /student/notifications
     * Danh sách thông báo sinh viên
     */
    public function index(array $params = []): void
    {
        $userId = $this->auth('id');
        $notifications = $this->db->select("
            SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY sent_at DESC
        ", [$userId]);

        $this->view('student/notifications/index', [
            'title'         => 'Thông báo của tôi',
            'notifications' => $notifications
        ]);
    }

    /**
     * POST /student/notifications/:id/read
     * Đánh dấu thông báo đã đọc
     */
    public function markRead(array $params = []): void
    {
        $this->verifyCsrf();
        $id = (int)$params['id'];
        $userId = $this->auth('id');

        $notif = $this->db->selectOne("SELECT * FROM notifications WHERE id = ?", [$id]);
        if (!$notif) {
            $this->jsonError('Thông báo không tồn tại', 404);
        }

        if ((int)$notif['user_id'] !== $userId) {
            $this->jsonError('Bạn không có quyền', 403);
        }

        try {
            $this->db->update('notifications', ['is_read' => 1], 'id = ?', [$id]);
            $this->jsonOk(null, 'Đã đánh dấu là đã đọc.');
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi cập nhật trạng thái.', 500);
        }
    }

    /**
     * GET /admin/notifications
     * Panel thông báo dành cho Admin
     */
    public function adminIndex(array $params = []): void
    {
        $this->requireAdmin();

        $notifications = $this->db->select("
            SELECT n.*, u.username AS receiver_username
            FROM notifications n
            LEFT JOIN users u ON u.id = n.user_id
            ORDER BY n.sent_at DESC
            LIMIT 50
        ");

        $students = $this->db->select("
            SELECT s.id, s.full_name, s.student_code, s.user_id 
            FROM students s
            ORDER BY s.student_code
        ");

        $this->view('admin/notifications/index', [
            'title'         => 'Thông báo hệ thống',
            'notifications' => $notifications,
            'students'      => $students
        ]);
    }

    /**
     * POST /admin/notifications/send
     * Gửi thông báo từ Admin (Cá nhân hoặc Broadcast)
     */
    public function send(array $params = []): void
    {
        $this->requireAdmin();
        $this->verifyCsrf();

        $target = $this->request('target', 'all'); // 'all' or specific student user_id
        $title  = $this->request('title', '');
        $message = $this->request('message', '');
        $type   = $this->request('type', 'general');

        if (!$title || !$message) {
            $this->jsonError('Tiêu đề và nội dung là bắt buộc', 422);
        }

        try {
            if ($target === 'all') {
                // Broadcast to all active users
                $users = $this->db->select("SELECT id FROM users WHERE status = 'active'");
                $this->db->transaction(function (Database $db) use ($users, $title, $message, $type) {
                    foreach ($users as $u) {
                        $db->insert('notifications', [
                            'user_id' => $u['id'],
                            'title'   => $title,
                            'message' => $message,
                            'type'    => $type
                        ]);
                    }
                });
            } else {
                // Send to specific student user_id
                $targetUserId = (int)$target;
                $this->db->insert('notifications', [
                    'user_id' => $targetUserId,
                    'title'   => $title,
                    'message' => $message,
                    'type'    => $type
                ]);
            }

            $this->jsonOk(null, 'Gửi thông báo thành công.', 201);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            $this->jsonError('Lỗi khi gửi thông báo.', 500);
        }
    }
}
