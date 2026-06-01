<?php
/**
 * app/views/student/invoices/show.php
 * Student — Chi tiết hóa đơn
 * Variables: $title, $invoice
 */

$id = (int)$invoice['id'];
$month = (int)$invoice['month'];
$year = (int)$invoice['year'];
$baseRent = (float)$invoice['base_rent'];
$elec = (float)$invoice['electricity_fee'];
$water = (float)$invoice['water_fee'];
$total = (float)$invoice['total_amount'];
$status = $invoice['status'] ?? 'unpaid';

$statusBadge = match($status) {
    'paid'    => '<span class="badge badge-success">Đã thanh toán</span>',
    'unpaid'  => '<span class="badge badge-warning">Chưa thanh toán</span>',
    'overdue' => '<span class="badge badge-danger">Quá hạn</span>',
    default   => '<span class="badge badge-neutral">' . htmlspecialchars($status) . '</span>'
};
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🧾 Chi tiết hóa đơn</h1>
    <p class="page-subtitle">Hóa đơn tháng <?= $month ?>/<?= $year ?> cho Phòng <?= htmlspecialchars($invoice['room_number']) ?></p>
  </div>
  <div class="page-actions">
    <a href="<?= getDynamicUrl('/student/invoices') ?>" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
  <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
    <h3 class="card-title">🧾 Hóa đơn số #<?= $id ?></h3>
    <?= $statusBadge ?>
  </div>
  
  <div class="card-body" style="display:flex;flex-direction:column;gap:18px;">
    
    <!-- Big total amount card -->
    <div style="text-align:center;background:var(--bg-neutral);padding:24px;border-radius:var(--radius-sm);border:1px solid var(--border);">
      <div style="font-size:11px;color:var(--txt-muted);font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Tổng cộng cần thanh toán</div>
      <strong style="font-size:28px;color:#ef4444;"><?= number_format($total) ?> ₫</strong>
    </div>

    <!-- Breakdown details -->
    <div>
      <h4 style="font-weight:700;color:var(--txt-secondary);margin-bottom:12px;text-transform:uppercase;font-size:12px;letter-spacing:.5px;">📊 Chi tiết các khoản phí</h4>
      <div style="display:flex;flex-direction:column;gap:10px;font-size:14px;">
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
          <span style="color:var(--txt-muted);">1. Tiền phòng cơ bản:</span>
          <strong><?= number_format($baseRent) ?> ₫</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
          <span style="color:var(--txt-muted);">2. Tiền điện tiêu thụ:</span>
          <strong><?= number_format($elec) ?> ₫</strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);">
          <span style="color:var(--txt-muted);">3. Tiền nước tiêu thụ:</span>
          <strong><?= number_format($water) ?> ₫</strong>
        </div>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px;font-size:13.5px;margin-top:10px;border-top:1px solid var(--border);padding-top:16px;">
      <div style="display:flex;justify-content:space-between;">
        <span style="color:var(--txt-muted);">Hạn nộp:</span>
        <strong style="color:var(--txt-primary);"><?= !empty($invoice['due_date']) ? date('d/m/Y', strtotime($invoice['due_date'])) : '--' ?></strong>
      </div>
      <?php if ($status === 'paid' && !empty($invoice['paid_at'])): ?>
        <div style="display:flex;justify-content:space-between;">
          <span style="color:var(--txt-muted);">Ngày đóng tiền:</span>
          <strong style="color:var(--txt-primary);"><?= date('d/m/Y H:i', strtotime($invoice['paid_at'])) ?></strong>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
    <a href="<?= getDynamicUrl('/api/invoices/' . $id . '/pdf') ?>" target="_blank" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:6px;">
      📥 Tải hóa đơn (PDF)
    </a>
    <?php if ($status !== 'paid'): ?>
      <button class="btn btn-primary" onclick="alert('Vui lòng liên hệ Văn phòng ban quản lý KTX để nộp tiền hoặc chuyển khoản theo cú pháp:\n[Dong tien KTX Phong <?= htmlspecialchars($invoice['room_number']) ?> Month <?= $month ?>]')">💳 Thanh toán trực tuyến</button>
    <?php endif; ?>
  </div>
</div>
