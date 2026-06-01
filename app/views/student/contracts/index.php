<?php
/**
 * app/views/student/contracts/index.php
 * Student — Danh sách hợp đồng của tôi
 * Variables: $title, $contracts[]
 */
?>

<div class="page-header">
  <div>
    <h1 class="page-title">📄 Hợp đồng của tôi</h1>
    <p class="page-subtitle">Xem lịch sử và trạng thái các hợp đồng thuê phòng</p>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Số hợp đồng</th>
          <th>Phòng ở</th>
          <th>Ngày bắt đầu</th>
          <th>Ngày kết thúc</th>
          <th>Tiền phòng/tháng</th>
          <th>Trạng thái</th>
          <th style="width:120px;text-align:center;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($contracts)): ?>
          <?php foreach ($contracts as $c): ?>
            <?php 
              $status = $c['status'] ?? 'active';
              $badge = match($status) {
                  'active'    => ['badge-success', 'Đang ở'],
                  'expired'   => ['badge-neutral', 'Hết hạn'],
                  'cancelled' => ['badge-danger',  'Đã hủy'],
                  default     => ['badge-neutral', $status]
              };
            ?>
            <tr>
              <td><strong>#<?= (int)$c['id'] ?></strong></td>
              <td><strong>🚪 Tòa <?= htmlspecialchars($c['building_name']) ?> - Phòng <?= htmlspecialchars($c['room_number']) ?></strong></td>
              <td style="color:var(--txt-secondary);"><?= date('d/m/Y', strtotime($c['start_date'])) ?></td>
              <td style="color:var(--txt-secondary);"><?= date('d/m/Y', strtotime($c['end_date'])) ?></td>
              <td style="font-weight:600;color:var(--txt-primary);"><?= number_format((float)$c['monthly_fee']) ?> ₫</td>
              <td><span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span></td>
              <td>
                <div style="display:flex;justify-content:center;">
                  <a href="<?= getDynamicUrl('/student/contracts/' . $c['id']) ?>" class="btn btn-ghost btn-sm">👁️ Chi tiết</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <div class="empty-icon">📄</div>
                <div class="empty-title">Chưa có hợp đồng nào</div>
                <div class="empty-msg">Hợp đồng sẽ được tạo khi đăng ký phòng của bạn được duyệt.</div>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
