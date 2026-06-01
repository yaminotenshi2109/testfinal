<?php
/**
 * app/views/admin/contracts/show.php
 * Admin — Chi tiết hợp đồng
 * Variables: $title, $contract
 */

$studentName = htmlspecialchars($contract['student_name']);
$studentCode = htmlspecialchars($contract['student_code']);
$phone       = htmlspecialchars($contract['phone']);
$roomNumber  = htmlspecialchars($contract['room_number']);
$roomType    = htmlspecialchars($contract['room_type']);
$building    = htmlspecialchars($contract['building_name']);
$startDate   = date('d/m/Y', strtotime($contract['start_date']));
$endDate     = date('d/m/Y', strtotime($contract['end_date']));
$monthlyFee  = number_format((float)$contract['monthly_fee']);
$status      = $contract['status'] ?? 'active';

$badge = match($status) {
    'active'    => ['badge-success', 'Đang hoạt động'],
    'expired'   => ['badge-neutral', 'Hết hạn'],
    'cancelled' => ['badge-danger',  'Đã hủy'],
    default     => ['badge-neutral', $status]
};
?>

<div class="page-header">
  <div>
    <h1 class="page-title">📄 Chi tiết hợp đồng</h1>
    <p class="page-subtitle">Xem chi tiết hợp đồng thuê phòng của sinh viên <?= $studentName ?></p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/contracts" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
  <div class="card-header">
    <h3 class="card-title">📝 Thông tin hợp đồng số #<?= (int)$contract['id'] ?></h3>
    <span class="badge <?= $badge[0] ?>" style="margin-left:auto;"><?= $badge[1] ?></span>
  </div>
  
  <div class="card-body" style="display:flex;flex-direction:column;gap:20px;">
    <!-- 2 columns -->
    <div class="grid-2" style="gap:24px;">
      <!-- Column 1: Student info -->
      <div>
        <h4 style="font-weight:700;color:var(--txt-secondary);margin-bottom:12px;text-transform:uppercase;font-size:12px;letter-spacing:.5px;">🎓 Bên thuê (Sinh viên)</h4>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:14px;">
          <div>Họ và tên: <strong><?= $studentName ?></strong></div>
          <div>Mã số sinh viên: <strong><?= $studentCode ?></strong></div>
          <div>Số điện thoại: <strong><?= $phone ?></strong></div>
        </div>
      </div>
      
      <!-- Column 2: Room info -->
      <div>
        <h4 style="font-weight:700;color:var(--txt-secondary);margin-bottom:12px;text-transform:uppercase;font-size:12px;letter-spacing:.5px;">🏠 Đối tượng thuê (Phòng ở)</h4>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:14px;">
          <div>Tòa nhà: <strong>Tòa <?= $building ?></strong></div>
          <div>Số phòng: <strong>Phòng <?= $roomNumber ?></strong></div>
          <div>Loại phòng: <strong><?= htmlspecialchars($roomType) ?></strong></div>
        </div>
      </div>
    </div>
    
    <hr style="border:0;border-top:1px solid var(--border);margin:10px 0;">
    
    <!-- Terms & Pricing -->
    <div>
      <h4 style="font-weight:700;color:var(--txt-secondary);margin-bottom:12px;text-transform:uppercase;font-size:12px;letter-spacing:.5px;">💸 Điều khoản & Đơn giá</h4>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:14px;">
        <div style="background:var(--bg-neutral);padding:14px;border-radius:var(--radius-sm);">
          <div style="font-size:11px;color:var(--txt-muted);font-weight:600;margin-bottom:4px;text-transform:uppercase;">Giá thuê hàng tháng</div>
          <strong style="font-size:18px;color:var(--txt-primary);"><?= $monthlyFee ?> ₫</strong>
        </div>
        <div style="background:var(--bg-neutral);padding:14px;border-radius:var(--radius-sm);">
          <div style="font-size:11px;color:var(--txt-muted);font-weight:600;margin-bottom:4px;text-transform:uppercase;">Thời hạn hợp đồng</div>
          <strong style="font-size:14px;color:var(--txt-primary);"><?= $startDate ?> – <?= $endDate ?></strong>
        </div>
      </div>
    </div>
  </div>
  
  <?php if ($status === 'active'): ?>
    <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px;">
      <button class="btn btn-danger" onclick="terminateContract(<?= (int)$contract['id'] ?>, '<?= $studentName ?>')">🛑 Chấm dứt hợp đồng</button>
    </div>
  <?php endif; ?>
</div>

<script>
function terminateContract(id, name) {
    if (!confirm('Bạn có chắc chắn muốn chấm dứt hợp đồng của sinh viên "' + name + '"?\nHành động này sẽ giải phóng chỗ trống trong phòng!')) return;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/testfinal/public/admin/contracts/' + id + '/terminate', {
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
            alert('✅ Đã chấm dứt hợp đồng thành công!');
            window.location.href = '/testfinal/public/admin/contracts';
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}
</script>
