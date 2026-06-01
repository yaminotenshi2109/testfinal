<?php
/**
 * app/views/admin/reports/revenue.php
 * Admin — Báo cáo doanh thu & hóa đơn
 * Variables: $title, $monthlyRevenue[], $paymentMethods[], $unpaidStats
 */

$totalCollected = 0;
foreach ($monthlyRevenue as $rev) {
    $totalCollected += (float)$rev['revenue'];
}
$unpaidCount = (int)($unpaidStats['count'] ?? 0);
$unpaidTotal = (float)($unpaidStats['total'] ?? 0);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">📈 Báo cáo doanh thu</h1>
    <p class="page-subtitle">Thống kê tài chính và các khoản thu hộ tiền phòng ký túc xá</p>
  </div>
</div>

<div class="stat-grid mb-24">
  <!-- Tổng đã thu -->
  <div class="stat-card" style="--stat-color:#10b981;--stat-icon-bg:#d1fae5;">
    <div class="stat-icon">💰</div>
    <div>
      <div class="stat-value"><?= number_format($totalCollected) ?>đ</div>
      <div class="stat-label">Tổng đã thu (12 tháng qua)</div>
    </div>
  </div>

  <!-- Chưa thu -->
  <div class="stat-card" style="--stat-color:#f59e0b;--stat-icon-bg:#fef3c7;">
    <div class="stat-icon">⏳</div>
    <div>
      <div class="stat-value"><?= number_format($unpaidTotal) ?>đ</div>
      <div class="stat-label">Chưa thu (<?= $unpaidCount ?> hóa đơn)</div>
    </div>
  </div>
</div>

<div class="grid-2" style="grid-template-columns: 2fr 1.2fr; gap: 24px;">
  <!-- Doanh thu theo tháng -->
  <div class="card" style="padding:24px;">
    <h3 style="font-size: 15px; font-weight: 800; color: var(--txt-primary); margin-bottom: 16px;">📊 Biểu đồ doanh thu 12 tháng qua</h3>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Thời gian</th>
            <th>Số hóa đơn đã thu</th>
            <th>Tổng doanh thu đã khớp</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($monthlyRevenue)): ?>
            <?php foreach ($monthlyRevenue as $r): ?>
              <tr>
                <td><strong>Tháng <?= (int)$r['month'] ?> / <?= (int)$r['year'] ?></strong></td>
                <td><strong><?= (int)$r['invoice_count'] ?></strong> hóa đơn</td>
                <td><strong style="color:var(--success);"><?= number_format((float)$r['revenue']) ?>đ</strong></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="3">
                <div class="empty-state">
                  <div class="empty-icon">📈</div>
                  <div class="empty-title">Chưa có dữ liệu doanh thu</div>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Phương thức thanh toán -->
  <div class="card" style="padding:24px;">
    <h3 style="font-size: 15px; font-weight: 800; color: var(--txt-primary); margin-bottom: 16px;">💳 Cơ cấu phương thức thanh toán</h3>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Phương thức</th>
            <th>Hóa đơn</th>
            <th>Số tiền</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($paymentMethods)): ?>
            <?php foreach ($paymentMethods as $pm): ?>
              <?php 
                $methodLabel = match($pm['payment_method']) {
                    'cash' => '💵 Tiền mặt',
                    'bank' => '🏦 Chuyển khoản',
                    'vnpay' => ' VNPay',
                    'momo' => ' MoMo',
                    default => $pm['payment_method']
                };
              ?>
              <tr>
                <td><strong><?= $methodLabel ?></strong></td>
                <td><?= (int)$pm['count'] ?> gd</td>
                <td><strong style="color:var(--txt-primary);"><?= number_format((float)$pm['total']) ?>đ</strong></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="3">
                <div class="empty-state">
                  <div class="empty-icon">💳</div>
                  <div class="empty-title">Chưa có thống kê phương thức</div>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
