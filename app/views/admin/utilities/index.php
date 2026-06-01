<?php
/**
 * app/views/admin/utilities/index.php
 * Admin — Danh sách chỉ số điện nước
 * Variables: $title, $readings[], $pagination[]
 */

$currentPage = (int)($pagination['current_page'] ?? 1);
$lastPage    = (int)($pagination['last_page']    ?? 1);
$total       = (int)($pagination['total']        ?? 0);
$from        = (int)($pagination['from']         ?? 0);
$to          = (int)($pagination['to']           ?? 0);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">⚡ Quản lý điện nước</h1>
    <p class="page-subtitle">Quản lý và ghi chỉ số tiêu thụ điện nước của từng phòng theo tháng</p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/utilities/create" class="btn btn-primary">➕ Ghi chỉ số mới</a>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Phòng</th>
          <th>Tháng/Năm</th>
          <th>Chỉ số Điện (đầu/cuối)</th>
          <th>Tiêu thụ Điện (kWh)</th>
          <th>Chỉ số Nước (đầu/cuối)</th>
          <th>Tiêu thụ Nước (m³)</th>
          <th>Ghi bởi</th>
          <th style="width:120px;text-align:center;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($readings)): ?>
          <?php foreach ($readings as $u): ?>
            <?php 
              $elecUsed  = (float)$u['elec_curr'] - (float)$u['elec_prev'];
              $waterUsed = (float)$u['water_curr'] - (float)$u['water_prev'];
            ?>
            <tr>
              <td><strong>🚪 Tòa <?= htmlspecialchars($u['building_name']) ?> - Phòng <?= htmlspecialchars($u['room_number']) ?></strong></td>
              <td><strong>Tháng <?= (int)$u['month'] ?>/<?= (int)$u['year'] ?></strong></td>
              <td style="color:var(--txt-secondary);">
                <?= number_format((float)$u['elec_prev'], 1) ?> – <strong><?= number_format((float)$u['elec_curr'], 1) ?></strong>
              </td>
              <td>
                <span class="badge badge-info">⚡ <?= number_format($elecUsed, 1) ?> kWh</span>
              </td>
              <td style="color:var(--txt-secondary);">
                <?= number_format((float)$u['water_prev'], 1) ?> – <strong><?= number_format((float)$u['water_curr'], 1) ?></strong>
              </td>
              <td>
                <span class="badge badge-purple">💧 <?= number_format($waterUsed, 1) ?> m³</span>
              </td>
              <td>
                <div style="font-size:12.5px;"><?= htmlspecialchars($u['recorder_username']) ?></div>
                <div class="sub" style="font-size:11px;"><?= date('d/m/Y', strtotime($u['recorded_at'])) ?></div>
              </td>
              <td>
                <div style="display:flex;gap:6px;justify-content:center;">
                  <a href="/testfinal/public/admin/utilities/<?= (int)$u['id'] ?>/edit" class="btn btn-ghost btn-sm">✏️ Sửa</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-icon">⚡</div>
                <div class="empty-title">Chưa có chỉ số điện nước nào</div>
                <div class="empty-msg">Bấm nút "Ghi chỉ số mới" để bắt đầu nhập chỉ số tiêu thụ cho các phòng.</div>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($lastPage > 1): ?>
    <div class="card-footer" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <span class="pagination-info">Trang <?= $currentPage ?> / <?= $lastPage ?></span>
      <div class="pagination" style="margin-left:auto;">
        <?php if ($currentPage > 1): ?>
          <a href="?page=<?= $currentPage - 1 ?>" class="page-link">‹</a>
        <?php endif; ?>
        <?php for ($p = 1; $p <= $lastPage; $p++): ?>
          <a href="?page=<?= $p ?>" class="page-link <?= $p === $currentPage ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($currentPage < $lastPage): ?>
          <a href="?page=<?= $currentPage + 1 ?>" class="page-link">›</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
