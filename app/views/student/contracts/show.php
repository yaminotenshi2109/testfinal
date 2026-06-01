<?php
/**
 * app/views/student/contracts/show.php
 * Student — Chi tiết hợp đồng
 * Variables: $title, $contract
 */

$roomNumber  = htmlspecialchars($contract['room_number']);
$roomType    = htmlspecialchars($contract['room_type']);
$building    = htmlspecialchars($contract['building_name']);
$manager     = htmlspecialchars($contract['manager_name']);
$phone       = htmlspecialchars($contract['manager_phone']);
$startDate   = date('d/m/Y', strtotime($contract['start_date']));
$endDate     = date('d/m/Y', strtotime($contract['end_date']));
$monthlyFee  = number_format((float)$contract['monthly_fee']);
$status      = $contract['status'] ?? 'active';

$badge = match($status) {
    'active'    => ['badge-success', 'Đang ở'],
    'expired'   => ['badge-neutral', 'Hết hạn'],
    'cancelled' => ['badge-danger',  'Đã hủy'],
    default     => ['badge-neutral', $status]
};
?>

<div class="page-header">
  <div>
    <h1 class="page-title">📄 Chi tiết hợp đồng</h1>
    <p class="page-subtitle">Xem thông tin chi tiết hợp đồng thuê phòng số #<?= (int)$contract['id'] ?></p>
  </div>
  <div class="page-actions">
    <a href="<?= getDynamicUrl('/student/contracts') ?>" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
  <div class="card-header">
    <h3 class="card-title">📝 Hợp đồng thuê phòng</h3>
    <span class="badge <?= $badge[0] ?>" style="margin-left:auto;"><?= $badge[1] ?></span>
  </div>
  <div class="card-body" style="display:flex;flex-direction:column;gap:18px;">
    
    <div style="display:flex;align-items:center;justify-content:space-between;background:var(--bg-neutral);padding:16px;border-radius:var(--radius-sm);">
      <div>
        <div style="font-size:11px;color:var(--txt-muted);font-weight:600;text-transform:uppercase;">Giá phòng thuê hàng tháng</div>
        <strong style="font-size:20px;color:var(--txt-primary);"><?= $monthlyFee ?> ₫</strong>
      </div>
      <div>
        <div style="font-size:11px;color:var(--txt-muted);font-weight:600;text-transform:uppercase;text-align:right;">Thời hạn</div>
        <strong style="font-size:14px;color:var(--txt-primary);"><?= $startDate ?> – <?= $endDate ?></strong>
      </div>
    </div>
    
    <div style="display:flex;flex-direction:column;gap:12px;font-size:13.5px;margin-top:10px;">
      <div style="display:flex;justify-content:space-between;">
        <span style="color:var(--txt-muted);">Tòa nhà:</span>
        <strong style="color:var(--txt-primary);">Tòa <?= $building ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;">
        <span style="color:var(--txt-muted);">Số phòng:</span>
        <strong style="color:var(--txt-primary);">Phòng <?= $roomNumber ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;">
        <span style="color:var(--txt-muted);">Loại phòng:</span>
        <strong style="color:var(--txt-primary);"><?= htmlspecialchars($roomType) ?></strong>
      </div>
      <hr style="border:0;border-top:1px solid var(--border);margin:10px 0;">
      <div style="display:flex;justify-content:space-between;">
        <span style="color:var(--txt-muted);">Quản lý tòa nhà:</span>
        <strong style="color:var(--txt-primary);"><?= $manager ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;">
        <span style="color:var(--txt-muted);">SĐT Quản lý:</span>
        <strong style="color:var(--txt-primary);"><?= $phone ?></strong>
      </div>
    </div>
    
  </div>
</div>
