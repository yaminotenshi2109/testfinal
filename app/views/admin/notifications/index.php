<?php
/**
 * app/views/admin/notifications/index.php
 * Admin — Gửi và quản lý thông báo
 * Variables: $title, $notifications[], $students[]
 */
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🔔 Thông báo hệ thống</h1>
    <p class="page-subtitle">Gửi thông báo và quản lý tin tức đến tài khoản sinh viên</p>
  </div>
</div>

<div class="grid-2" style="grid-template-columns: 1fr 1.8fr; gap: 24px;">
  <!-- Left Side: Send Notification Form -->
  <div class="card" style="padding: 24px; height: fit-content;">
    <h3 style="font-size: 15px; font-weight: 800; color: var(--txt-primary); margin-bottom: 16px;">📣 Tạo thông báo mới</h3>
    <form id="formSendNotif" onsubmit="sendNotification(event)">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Đối tượng nhận <span class="req">*</span></label>
        <select name="target" class="form-control" required>
          <option value="all">📢 Gửi tất cả tài khoản sinh viên (Broadcast)</option>
          <option disabled>──────────</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= (int)$s['user_id'] ?>">🎓 <?= htmlspecialchars($s['full_name']) ?> (MSV: <?= htmlspecialchars($s['student_code']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Phân loại thông báo <span class="req">*</span></label>
        <select name="type" class="form-control" required>
          <option value="general">🔔 Chung</option>
          <option value="invoice">💳 Tiền phòng / Hóa đơn</option>
          <option value="violation">⚠️ Vi phạm / Phạt điểm</option>
          <option value="contract">📄 Hợp đồng</option>
          <option value="system">⚙️ Hệ thống</option>
        </select>
      </div>

      <div class="form-group" style="margin-bottom: 14px;">
        <label class="form-label">Tiêu đề thông báo <span class="req">*</span></label>
        <input type="text" name="title" class="form-control" placeholder="Nhập tiêu đề ngắn gọn..." required>
      </div>

      <div class="form-group" style="margin-bottom: 18px;">
        <label class="form-label">Nội dung chi tiết <span class="req">*</span></label>
        <textarea name="message" class="form-control" rows="5" placeholder="Nhập nội dung thông báo đầy đủ..." required></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">🚀 Gửi thông báo ngay</button>
    </form>
  </div>

  <!-- Right Side: Recent Notifications History -->
  <div class="card" style="padding: 24px;">
    <h3 style="font-size: 15px; font-weight: 800; color: var(--txt-primary); margin-bottom: 16px;">🕒 Lịch sử gửi thông báo gần đây (50 tin mới nhất)</h3>
    
    <?php if (!empty($notifications)): ?>
      <div class="notif-list" style="max-height: 500px; overflow-y: auto;">
        <?php foreach ($notifications as $n): ?>
          <?php 
            $type = $n['type'] ?? 'general';
            $icon = match($type) {
                'invoice'   => '💳',
                'violation' => '⚠️',
                'contract'  => '📄',
                'system'    => '⚙️',
                default     => '🔔'
            };
            $receiver = $n['receiver_username'] ? '👤 Nhận bởi: @' . htmlspecialchars($n['receiver_username']) : '📢 Phát sóng (Broadcast)';
          ?>
          <div class="notif-item" style="padding: 14px; border-bottom: 1px solid var(--border); display: flex; gap: 12px;">
            <div style="font-size: 20px; flex-shrink: 0;"><?= $icon ?></div>
            <div style="flex: 1; min-width: 0;">
              <h4 style="font-size: 13.5px; font-weight: 700; color: var(--txt-primary); margin: 0 0 2px 0;"><?= htmlspecialchars($n['title']) ?></h4>
              <p style="color: var(--txt-secondary); font-size: 12.5px; margin: 0 0 6px 0;"><?= htmlspecialchars($n['message']) ?></p>
              <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--txt-muted);">
                <span><?= $receiver ?></span>
                <span>🕐 <?= date('d/m/Y H:i', strtotime($n['sent_at'])) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state" style="padding: 40px 24px;">
        <div class="empty-icon">📭</div>
        <div class="empty-title">Chưa có thông báo nào được gửi</div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function sendNotification(e) {
    e.preventDefault();
    const form = document.getElementById('formSendNotif');
    const data = new FormData(form);
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('/testfinal/public/admin/notifications/send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': token
        },
        body: JSON.stringify(Object.fromEntries(data))
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            alert('🚀 Gửi thông báo thành công!');
            location.reload();
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}
</script>
