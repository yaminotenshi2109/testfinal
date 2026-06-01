<?php
/**
 * app/views/admin/registrations/show.php
 * Admin — Chi tiết đơn đăng ký phòng
 * Variables: $title, $registration, $availableRooms[]
 */

$id          = (int)$registration['id'];
$studentName = htmlspecialchars($registration['full_name']);
$studentCode = htmlspecialchars($registration['student_code']);
$phone       = htmlspecialchars($registration['phone']);
$priority    = (int)$registration['priority_level'];
$prefBuild   = htmlspecialchars($registration['building_name'] ?? 'Không yêu cầu');
$prefType    = htmlspecialchars($registration['preferred_room_type'] ?? 'Không yêu cầu');
$notes       = htmlspecialchars($registration['notes'] ?? '—');
$status      = $registration['status'] ?? 'pending';

$priorities = [
    0 => 'Thường',
    1 => 'Chính sách⭐',
    2 => 'Ưu tiên cao⭐⭐'
];

$statusBadge = match($status) {
    'pending'  => 'badge-warning',
    'approved' => 'badge-success',
    'rejected' => 'badge-danger',
    default    => 'badge-neutral'
};
$statusLabel = match($status) {
    'pending'  => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Bị từ chối',
    default    => $status
};
?>

<div class="page-header">
  <div>
    <h1 class="page-title">📋 Chi tiết đơn đăng ký</h1>
    <p class="page-subtitle">Duyệt và xếp phòng cho sinh viên <?= $studentName ?></p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/registrations" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="grid-2" style="grid-template-columns: 1.5fr 1fr; gap: 24px;">
  <!-- Left Column: Details -->
  <div style="display:flex;flex-direction:column;gap:24px;">
    <!-- Main Info -->
    <div class="card" style="padding:24px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <span class="badge badge-info" style="font-size:11px;font-weight:700;"><?= $priorities[$priority] ?? 'Thường' ?></span>
        <span class="badge <?= $statusBadge ?>"><?= $statusLabel ?></span>
      </div>

      <div style="display:flex;flex-direction:column;gap:14px;font-size:14px;">
        <div>Sinh viên: <strong><?= $studentName ?></strong> (MSV: <?= $studentCode ?>)</div>
        <div>Số điện thoại: <strong><?= $phone ?></strong></div>
        <hr style="border:0;border-top:1px solid var(--border);margin:8px 0;">
        <div>Tòa nhà ưa thích: <strong><?= $prefBuild ?></strong></div>
        <div>Loại phòng ưa thích: <strong><?= $prefType ?></strong></div>
        <div>Học kỳ đăng ký: <strong>Học kỳ <?= htmlspecialchars($registration['semester']) ?> / Năm học <?= htmlspecialchars($registration['academic_year']) ?></strong></div>
        <div style="display:flex;flex-direction:column;gap:4px;">
          <span>Ghi chú của sinh viên:</span>
          <strong style="color:var(--txt-secondary);line-height:1.4;"><?= $notes ?></strong>
        </div>
      </div>
    </div>

    <!-- Allocation Panel (Only if pending) -->
    <?php if ($status === 'pending'): ?>
      <div class="card" style="padding:24px;">
        <h3 style="font-size:15px;font-weight:800;color:var(--txt-primary);margin-bottom:16px;">🏢 Phân phối xếp phòng ở</h3>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
          <!-- Option A: Auto-Allocate -->
          <div style="background:var(--bg-neutral);padding:16px;border-radius:var(--radius-sm);text-align:center;display:flex;flex-direction:column;justify-content:space-between;">
            <div>
              <h4 style="font-weight:700;font-size:13.5px;margin-bottom:6px;">Tự động xếp phòng</h4>
              <p style="color:var(--txt-muted);font-size:12px;margin-bottom:12px;">Hệ thống sẽ tự động gán sinh viên vào phòng trống tối ưu thuộc tòa nhà ưa thích.</p>
            </div>
            <button class="btn btn-primary btn-sm" style="width:100%;" onclick="autoAllocate()">🚀 Tự động xếp phòng</button>
          </div>

          <!-- Option B: Manual Allocate -->
          <div style="background:var(--bg-neutral);padding:16px;border-radius:var(--radius-sm);display:flex;flex-direction:column;justify-content:space-between;">
            <div>
              <h4 style="font-weight:700;font-size:13.5px;margin-bottom:6px;text-align:center;">Xếp phòng thủ công</h4>
              <p style="color:var(--txt-muted);font-size:12px;margin-bottom:10px;text-align:center;">Chủ động chọn một phòng trống cụ thể dưới đây.</p>
              <select id="manualRoomSelect" class="form-control" style="margin-bottom:12px;">
                <option value="">-- Chọn phòng trống --</option>
                <?php foreach ($availableRooms as $r): ?>
                  <option value="<?= (int)$r['id'] ?>">Tòa <?= htmlspecialchars($r['building_name']) ?> - Phòng <?= htmlspecialchars($r['room_number']) ?> (Trống: <?= (int)$r['capacity'] - (int)$r['current_occupants'] ?> giường)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <button class="btn btn-outline btn-sm" style="width:100%;" onclick="manualAllocate()">💾 Gán phòng chọn</button>
          </div>
        </div>
      </div>
    <?php else: ?>
      <?php if ($status === 'approved' && !empty($registration['assigned_room_id'])): ?>
        <div class="card" style="padding:24px;background:rgba(34,197,94,0.05);border:1px solid rgba(34,197,94,0.2);">
          <h3 style="font-size:14px;font-weight:800;color:var(--success);margin-bottom:8px;">✅ Đã gán phòng thành công</h3>
          <p style="font-size:13.5px;margin:0;">Sinh viên đã được gán vào <strong>Phòng <?= htmlspecialchars($registration['room_number']) ?> (Tầng <?= htmlspecialchars($registration['floor']) ?>)</strong> thuộc <strong>Tòa <?= htmlspecialchars($registration['building_name']) ?></strong>.</p>
        </div>
      <?php elseif ($status === 'rejected'): ?>
        <div class="card" style="padding:24px;background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.2);">
          <h3 style="font-size:14px;font-weight:800;color:var(--danger);margin-bottom:8px;">❌ Đơn đăng ký bị từ chối</h3>
          <p style="font-size:13.5px;margin:0;">Lý do từ chối: <strong><?= htmlspecialchars($registration['reject_reason'] ?? 'Không có lý do chi tiết.') ?></strong></p>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Right Column: Quick Reject (Only if pending) -->
  <?php if ($status === 'pending'): ?>
    <div class="card" style="padding:20px;height:fit-content;">
      <h3 style="font-size:14px;font-weight:800;color:var(--txt-secondary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:10px;">❌ Từ chối đơn đăng ký</h3>
      <div class="form-group" style="margin-bottom:12px;">
        <label class="form-label">Lý do từ chối <span class="req">*</span></label>
        <textarea id="rejectReasonInput" class="form-control" rows="3" placeholder="Nhập lý do chi tiết..." required></textarea>
      </div>
      <button class="btn btn-danger" style="width:100%;" onclick="rejectRegistration()">Từ chối đơn</button>
    </div>
  <?php endif; ?>
</div>

<script>
function autoAllocate() {
    if (!confirm('Xác nhận tự động xếp phòng cho sinh viên này?')) return;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/testfinal/public/api/registrations/<?= $id ?>/auto-allocate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': token
        },
        body: JSON.stringify({ method: 'auto' })
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            alert('🚀 Tự động xếp phòng thành công!');
            location.reload();
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}

function manualAllocate() {
    const roomId = document.getElementById('manualRoomSelect').value;
    if (!roomId) { alert('Vui lòng chọn phòng trống!'); return; }
    
    if (!confirm('Xác nhận gán sinh viên vào phòng đã chọn?')) return;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/testfinal/public/api/registrations/<?= $id ?>/manual-allocate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': token
        },
        body: JSON.stringify({ room_id: roomId })
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            alert('✅ Gán phòng thủ công thành công!');
            location.reload();
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}

function rejectRegistration() {
    const reason = document.getElementById('rejectReasonInput').value.trim();
    if (!reason) { alert('Vui lòng nhập lý do từ chối đơn!'); return; }
    
    if (!confirm('Xác nhận từ chối đơn đăng ký này?')) return;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/testfinal/public/api/registrations/<?= $id ?>/reject', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': token
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            alert('❌ Đã từ chối đơn đăng ký thành công!');
            location.reload();
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}
</script>
