<?php
/**
 * app/views/admin/maintenance/show.php
 * Admin — Chi tiết sự cố bảo trì
 * Variables: $title, $request
 */

$id          = (int)$request['id'];
$roomNumber  = htmlspecialchars($request['room_number']);
$building    = htmlspecialchars($request['building_name']);
$reporter    = htmlspecialchars($request['reporter_name'] ?? $request['reporter_username']);
$phone       = htmlspecialchars($request['reporter_phone'] ?? '—');
$issueTitle  = htmlspecialchars($request['title']);
$desc        = htmlspecialchars($request['description']);
$priority    = $request['priority'] ?? 'medium';
$status      = $request['status'] ?? 'open';
$resolution  = htmlspecialchars($request['resolution'] ?? '');

$priorityBadge = match($priority) {
    'low'    => 'badge-neutral',
    'medium' => 'badge-info',
    'high'   => 'badge-warning',
    'urgent' => 'badge-danger',
    default  => 'badge-neutral'
};
$priorityLabel = match($priority) {
    'low'    => 'Thấp',
    'medium' => 'Trung bình',
    'high'   => 'Cao',
    'urgent' => 'Khẩn cấp 🚨',
    default  => $priority
};

$statusBadge = match($status) {
    'open'        => 'badge-danger',
    'in_progress' => 'badge-warning',
    'resolved'    => 'badge-success',
    'closed'      => 'badge-neutral',
    default       => 'badge-neutral'
};
$statusLabel = match($status) {
    'open'        => 'Chưa xử lý',
    'in_progress' => 'Đang xử lý',
    'resolved'    => 'Đã xử lý',
    'closed'      => 'Đã đóng',
    default       => $status
};
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🔧 Chi tiết sự cố bảo trì</h1>
    <p class="page-subtitle">Sự cố báo cáo tại Phòng <?= $roomNumber ?> (Tòa <?= $building ?>)</p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/maintenance" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="grid-2" style="grid-template-columns: 1.8fr 1fr; gap: 24px;">
  <!-- Left Side: Issue Details & Resolution -->
  <div style="display:flex;flex-direction:column;gap:24px;">
    <!-- Issue Card -->
    <div class="card" style="padding:24px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <span class="badge <?= $priorityBadge ?>" style="font-size:11px;font-weight:700;"><?= $priorityLabel ?></span>
        <span class="badge <?= $statusBadge ?>"><?= $statusLabel ?></span>
      </div>
      
      <h2 style="font-size:18px;font-weight:800;color:var(--txt-primary);margin-bottom:12px;"><?= $issueTitle ?></h2>
      <p style="color:var(--txt-secondary);line-height:1.6;font-size:14px;white-space:pre-wrap;"><?= $desc ?></p>
      
      <div style="font-size:11px;color:var(--txt-muted);margin-top:16px;border-top:1px solid var(--border);padding-top:12px;">
        🕐 Báo cáo lúc: <?= date('d/m/Y H:i', strtotime($request['reported_at'])) ?>
      </div>
    </div>

    <!-- Resolution Card -->
    <div class="card" style="padding:24px;">
      <h3 style="font-size:15px;font-weight:800;color:var(--txt-primary);margin-bottom:16px;">🛠️ Phương án xử lý sự cố</h3>
      
      <?php if ($status === 'open' || $status === 'in_progress'): ?>
        <form id="formResolve" onsubmit="submitResolve(event)">
          <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label">Giải pháp / Cách thức sửa chữa <span class="req">*</span></label>
            <textarea name="resolution" class="form-control" rows="4" placeholder="Ví dụ: Đã cử thợ điện đến thay bóng đèn mới..." required><?= $resolution ?></textarea>
          </div>
          <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="btn btn-primary">🔧 Xác nhận đã xử lý xong</button>
          </div>
        </form>
      <?php else: ?>
        <div style="background:var(--bg-neutral);padding:16px;border-radius:var(--radius-sm);font-size:13.5px;line-height:1.5;">
          <div style="font-weight:700;margin-bottom:6px;">Phương án đã thực hiện:</div>
          <p style="color:var(--txt-secondary);margin:0;"><?= $resolution ?: 'Không có mô tả chi tiết.' ?></p>
          <?php if (!empty($request['resolved_at'])): ?>
            <div style="font-size:11px;color:var(--txt-muted);margin-top:12px;border-top:1px solid var(--border);padding-top:8px;">
              ✅ Xử lý xong ngày: <?= date('d/m/Y H:i', strtotime($request['resolved_at'])) ?>
            </div>
          <?php endif; ?>
        </div>
        
        <?php if ($status === 'resolved'): ?>
          <div style="display:flex;justify-content:flex-end;margin-top:16px;">
            <button class="btn btn-neutral" onclick="closeRequest(<?= $id ?>)">🔒 Đóng yêu cầu bảo trì</button>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Side: Reporter & Room info -->
  <div class="card" style="padding:20px;height:fit-content;">
    <h3 style="font-size:14px;font-weight:800;color:var(--txt-secondary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:10px;">📋 Người báo cáo & Địa điểm</h3>
    
    <div style="display:flex;flex-direction:column;gap:12px;font-size:13.5px;">
      <div>
        <span style="color:var(--txt-muted);">Sinh viên:</span>
        <strong style="color:var(--txt-primary);display:block;margin-top:2px;"><?= $reporter ?></strong>
      </div>
      <div>
        <span style="color:var(--txt-muted);">Số điện thoại:</span>
        <strong style="color:var(--txt-primary);display:block;margin-top:2px;"><?= $phone ?></strong>
      </div>
      <hr style="border:0;border-top:1px solid var(--border);margin:8px 0;">
      <div>
        <span style="color:var(--txt-muted);">Tòa nhà:</span>
        <strong style="color:var(--txt-primary);display:block;margin-top:2px;">Tòa <?= $building ?></strong>
      </div>
      <div>
        <span style="color:var(--txt-muted);">Số phòng:</span>
        <strong style="color:var(--txt-primary);display:block;margin-top:2px;">Phòng <?= $roomNumber ?></strong>
      </div>
    </div>
  </div>
</div>

<script>
function submitResolve(e) {
    e.preventDefault();
    const form = document.getElementById('formResolve');
    const data = new FormData(form);
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/testfinal/public/admin/maintenance/<?= $id ?>/resolve', {
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
            alert('✅ Đã cập nhật trạng thái xử lý thành công!');
            location.reload();
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}

function closeRequest(id) {
    if (!confirm('Bạn có chắc chắn muốn đóng yêu cầu bảo trì này?\nHành động này biểu thị sự cố đã được nghiệm thu và đóng vĩnh viễn.')) return;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/testfinal/public/admin/maintenance/' + id + '/close', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': token
        }
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            alert('✅ Đã đóng yêu cầu bảo trì thành công!');
            location.reload();
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}
</script>
