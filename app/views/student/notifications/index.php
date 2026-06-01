<?php
/**
 * app/views/student/notifications/index.php
 * Student — Thông báo
 */

$unreadCount = 0;
foreach ($notifications ?? [] as $n) {
    if (!($n['is_read'] ?? false)) $unreadCount++;
}

function notifTypeIcon(string $type): string {
    return match($type) {
        'registration' => '📋',
        'contract'     => '📄',
        'invoice'      => '🧾',
        'violation'    => '⚠️',
        'maintenance'  => '🔧',
        'system'       => '⚙️',
        default        => '🔔',
    };
}
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🔔 Thông báo</h1>
    <p class="page-subtitle">
      <?php if ($unreadCount > 0): ?>
        Bạn có <strong><?= $unreadCount ?></strong> thông báo chưa đọc
      <?php else: ?>
        Tất cả thông báo đã được đọc
      <?php endif; ?>
    </p>
  </div>
  <?php if ($unreadCount > 0): ?>
    <div class="page-actions">
      <button class="btn btn-outline btn-sm" id="btnMarkAllRead">
        ✅ Đánh dấu tất cả đã đọc
      </button>
    </div>
  <?php endif; ?>
</div>

<!-- Filter Tabs -->
<div class="tabs" id="notifTabs">
  <button class="tab-link active" data-filter="all">Tất cả (<?= count($notifications ?? []) ?>)</button>
  <button class="tab-link" data-filter="unread">Chưa đọc (<?= $unreadCount ?>)</button>
</div>

<?php if (empty($notifications)): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-icon">🔔</div>
      <h3 class="empty-title">Chưa có thông báo nào</h3>
      <p class="empty-msg">Bạn sẽ nhận được thông báo khi có cập nhật về đăng ký phòng, hóa đơn, và các sự kiện khác.</p>
    </div>
  </div>

<?php else: ?>
  <div class="card" style="overflow:hidden">
    <div class="notif-list" id="notifList">
      <?php foreach ($notifications as $notif): ?>
        <?php $isUnread = !(bool)($notif['is_read'] ?? false); ?>
        <div class="notif-item <?= $isUnread ? 'unread' : '' ?>"
             data-id="<?= (int)($notif['id'] ?? 0) ?>"
             data-read="<?= $isUnread ? '0' : '1' ?>"
             onclick="markRead(this)">

          <div class="notif-dot"></div>

          <div style="font-size:22px;flex-shrink:0;margin-top:2px">
            <?= notifTypeIcon($notif['type'] ?? 'system') ?>
          </div>

          <div class="notif-body">
            <div class="notif-title" style="<?= $isUnread ? 'font-weight:700' : '' ?>">
              <?= htmlspecialchars($notif['title'] ?? '') ?>
            </div>
            <div class="notif-msg"><?= htmlspecialchars($notif['message'] ?? '') ?></div>
          </div>

          <div class="notif-time">
            <?php
              $sent = $notif['sent_at'] ?? null;
              if ($sent) {
                  $ts   = strtotime($sent);
                  $diff = time() - $ts;
                  if ($diff < 3600)       echo round($diff / 60) . ' phút trước';
                  elseif ($diff < 86400)  echo round($diff / 3600) . ' giờ trước';
                  elseif ($diff < 604800) echo round($diff / 86400) . ' ngày trước';
                  else                    echo date('d/m/Y', $ts);
              }
            ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<script>
// Tab filter
document.querySelectorAll('#notifTabs .tab-link').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('#notifTabs .tab-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const filter = btn.dataset.filter;
    document.querySelectorAll('#notifList .notif-item').forEach(item => {
      if (filter === 'all') {
        item.style.display = '';
      } else {
        item.style.display = item.dataset.read === '0' ? '' : 'none';
      }
    });
  });
});

// Mark single notification as read
async function markRead(el) {
  if (el.dataset.read === '1') return;
  const id   = el.dataset.id;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  try {
    const targetUrl = '<?= getDynamicUrl('/api/notifications/') ?>' + id + '/read';
    await window.ktx.ktxFetch(targetUrl, {
      method: 'POST',
      body: JSON.stringify({ _csrf_token: csrf }),
    });
    el.classList.remove('unread');
    el.dataset.read = '1';
  } catch (e) { /* ignore */ }
}

// Mark all as read
document.getElementById('btnMarkAllRead')?.addEventListener('click', async () => {
  const btn  = document.getElementById('btnMarkAllRead');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  btn.disabled = true;
  btn.innerHTML = '<span class="loading"></span>';

  try {
    await window.ktx.ktxFetch('<?= getDynamicUrl('/api/notifications/read-all') ?>', {
      method: 'POST',
      body: JSON.stringify({ _csrf_token: csrf }),
    });
    document.querySelectorAll('#notifList .notif-item.unread').forEach(el => {
      el.classList.remove('unread');
      el.dataset.read = '1';
    });
    btn.remove();
    window.ktx.toast('Đã đánh dấu tất cả thông báo là đã đọc', 'success');
  } catch (e) {
    window.ktx.toast('Có lỗi xảy ra', 'error');
    btn.disabled = false;
    btn.innerHTML = '✅ Đánh dấu tất cả đã đọc';
  }
});
</script>
