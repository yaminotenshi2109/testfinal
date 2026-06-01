<?php
/**
 * app/controllers/NotificationApiController.php
 * JSON API for Notifications
 */

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseController.php';

class NotificationApiController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(array $params = []): void
    {
        $userId = $this->auth('id');
        $notifications = $this->db->select("
            SELECT * FROM notifications
            WHERE user_id = ?
            ORDER BY sent_at DESC
        ", [$userId]);

        $this->jsonOk($notifications, 'Lấy danh sách thông báo thành công.');
    }

    public function markRead(array $params = []): void
    {
        $this->verifyCsrf();
        $id = (int)$params['id'];
        $userId = $this->auth('id');

        $notif = $this->db->selectOne("SELECT * FROM notifications WHERE id = ?", [$id]);
        if (!$notif || (int)$notif['user_id'] !== $userId) {
            $this->jsonError('Thông báo không tồn tại', 404);
        }

        try {
            $this->db->update('notifications', ['is_read' => 1], 'id = ?', [$id]);
            $this->jsonOk(null, 'Đã đánh dấu là đã đọc.');
        } catch (\Throwable $e) {
            $this->jsonError('Lỗi cập nhật', 500);
        }
    }

    public function markAllRead(array $params = []): void
    {
        $this->verifyCsrf();
        $userId = $this->auth('id');

        try {
            $this->db->update('notifications', ['is_read' => 1], 'user_id = ?', [$userId]);
            $this->jsonOk(null, 'Đã đánh dấu tất cả thông báo là đã đọc.');
        } catch (\Throwable $e) {
            $this->jsonError('Lỗi cập nhật', 500);
        }
    }
}
