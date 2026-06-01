<?php
/**
 * app/views/student/invoices/index.php
 * Student — Danh sách hóa đơn
 */

// Tính tổng thống kê
$totalPaid   = 0;
$totalUnpaid = 0;
foreach ($invoices ?? [] as $inv) {
    if ($inv['status'] === 'paid') {
        $totalPaid += (float)($inv['total_amount'] ?? 0);
    } elseif (in_array($inv['status'], ['unpaid', 'overdue'])) {
        $totalUnpaid += (float)($inv['total_amount'] ?? 0);
    }
}

function invoiceStatusBadge(string $status): string {
    return match($status) {
        'paid'      => '<span class="badge badge-success">✅ Đã thanh toán</span>',
        'unpaid'    => '<span class="badge badge-warning">⏳ Chưa thanh toán</span>',
        'overdue'   => '<span class="badge badge-danger">🔴 Quá hạn</span>',
        'cancelled' => '<span class="badge badge-neutral">Đã hủy</span>',
        default     => '<span class="badge badge-neutral">' . htmlspecialchars($status) . '</span>',
    };
}
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🧾 Hóa đơn của tôi</h1>
    <p class="page-subtitle">Theo dõi và thanh toán hóa đơn hàng tháng</p>
  </div>
</div>

<!-- Summary stats -->
<div class="stat-grid mb-24" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr))">
  <div class="stat-card" style="--stat-color:#10b981;--stat-icon-bg:#d1fae5">
    <div class="stat-icon">✅</div>
    <div>
      <div class="stat-value" style="font-size:18px"><?= number_format($totalPaid) ?> ₫</div>
      <div class="stat-label">Tổng đã thanh toán</div>
    </div>
  </div>
  <div class="stat-card" style="--stat-color:#f59e0b;--stat-icon-bg:#fef3c7">
    <div class="stat-icon">⏳</div>
    <div>
      <div class="stat-value" style="font-size:18px"><?= number_format($totalUnpaid) ?> ₫</div>
      <div class="stat-label">Tổng chưa thanh toán</div>
    </div>
  </div>
</div>

<?php if (empty($invoices)): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-icon">🧾</div>
      <h3 class="empty-title">Chưa có hóa đơn nào</h3>
      <p class="empty-msg">Hóa đơn sẽ được tạo tự động vào đầu mỗi tháng khi bạn có hợp đồng phòng.</p>
    </div>
  </div>

<?php else: ?>
  <div style="display:flex;flex-direction:column;gap:12px">
    <?php foreach ($invoices as $inv): ?>
      <?php
        $isOverdue = $inv['status'] === 'overdue' || ($inv['status'] === 'unpaid' && strtotime($inv['due_date'] ?? '') < time());
        $elecWater = (float)($inv['electricity_fee'] ?? 0) + (float)($inv['water_fee'] ?? 0) + (float)($inv['ac_fee'] ?? 0);
      ?>
      <div class="invoice-card" style="<?= $isOverdue ? 'border-color:var(--danger);border-width:2px;' : '' ?> display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--card-bg);">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1;">
          <div class="invoice-icon" style="font-size: 24px;"><?= $inv['status'] === 'paid' ? '✅' : ($isOverdue ? '🔴' : '🧾') ?></div>

          <div class="invoice-info">
            <div class="invoice-month" style="font-weight: 700; font-size: 15px; color: var(--txt-primary);">
              Tháng <?= htmlspecialchars((string)($inv['month'] ?? '')) ?>/<?= htmlspecialchars((string)($inv['year'] ?? '')) ?>
            </div>
            <div class="invoice-detail" style="font-size: 13px; color: var(--txt-secondary); margin-top: 2px;">
              📍 <?= htmlspecialchars($inv['building_name'] ?? '') ?> — Phòng <?= htmlspecialchars($inv['room_number'] ?? '') ?>
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:6px;font-size:12px;color:var(--txt-muted)">
              <span>🏠 Tiền phòng: <strong><?= number_format((float)($inv['base_rent'] ?? 0)) ?> ₫</strong></span>
              <span>⚡ Điện+Nước: <strong><?= number_format($elecWater) ?> ₫</strong></span>
            </div>
          </div>
        </div>

        <div style="text-align:right; flex-shrink:0; display: flex; flex-direction: column; align-items: flex-end;">
          <?= invoiceStatusBadge($inv['status'] ?? '') ?>
          <div class="invoice-amount" style="margin-top:8px; font-weight: 700; font-size: 16px; color: var(--txt-primary);"><?= number_format((float)($inv['total_amount'] ?? 0)) ?> ₫</div>
          <div style="font-size:11px;color:var(--txt-muted);margin-top:4px">
            <?php if ($inv['status'] === 'paid' && !empty($inv['paid_at'])): ?>
              ✅ Thanh toán: <?= date('d/m/Y', strtotime($inv['paid_at'])) ?>
            <?php elseif (!empty($inv['due_date'])): ?>
              📅 Hạn: <span style="color:<?= $isOverdue ? 'var(--danger)' : 'inherit' ?>;font-weight:<?= $isOverdue ? '600' : '400' ?>">
                <?= date('d/m/Y', strtotime($inv['due_date'])) ?>
              </span>
            <?php endif; ?>
          </div>
          <a href="<?= getDynamicUrl('/student/invoices/' . $inv['id']) ?>" class="btn btn-ghost btn-sm" style="margin-top: 8px;">👁️ Xem chi tiết</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
