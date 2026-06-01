<?php
/**
 * app/views/admin/buildings/show.php
 * Admin — Chi tiết tòa nhà
 * Variables: $title, $building, $rooms[]
 */

$name    = htmlspecialchars($building['name']);
$floors  = (int)$building['total_floors'];
$gender  = $building['gender_type'] === 'male' ? 'Nam' : ($building['gender_type'] === 'female' ? 'Nữ' : 'Hỗn hợp');
$mName   = htmlspecialchars($building['manager_name']);
$mPhone  = htmlspecialchars($building['manager_phone']);
$address = htmlspecialchars($building['address']);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🏢 Chi tiết tòa nhà <?= $name ?></h1>
    <p class="page-subtitle">Xem chi tiết cơ cấu và danh sách phòng</p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/buildings" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="grid-2" style="grid-template-columns: 1fr 2.5fr; gap: 24px;">
  <!-- Left Column: Building Details Card -->
  <div class="card" style="padding: 20px;">
    <div style="text-align: center; margin-bottom: 20px;">
      <div style="font-size: 48px; margin-bottom: 10px;">🏢</div>
      <h3 style="font-size: 18px; font-weight: 800; color: var(--txt-primary); margin: 0;">Tòa <?= $name ?></h3>
    </div>
    
    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13.5px; border-top: 1px solid var(--border); padding-top: 16px;">
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">Số tầng:</span>
        <strong style="color: var(--txt-primary);"><?= $floors ?> tầng</strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">Giới tính ở:</span>
        <strong style="color: var(--txt-primary);"><?= $gender ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">Quản lý tòa:</span>
        <strong style="color: var(--txt-primary);"><?= $mName ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">SĐT Quản lý:</span>
        <strong style="color: var(--txt-primary);"><?= $mPhone ?></strong>
      </div>
      <div style="display: flex; flex-direction: column; gap: 4px;">
        <span style="color: var(--txt-muted);">Địa chỉ:</span>
        <strong style="color: var(--txt-primary); line-height: 1.4;"><?= $address ?></strong>
      </div>
    </div>
  </div>

  <!-- Right Column: Rooms List Table -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">🚪 Danh sách phòng thuộc Tòa <?= $name ?></h3>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Số phòng</th>
            <th>Tầng</th>
            <th>Loại phòng</th>
            <th>Sức chứa</th>
            <th>Đang ở</th>
            <th>Đơn giá</th>
            <th>Trạng thái</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rooms)): ?>
            <?php foreach ($rooms as $r): ?>
              <?php
                $statusClass = match($r['status']) {
                    'available'   => 'badge-success',
                    'full'        => 'badge-danger',
                    'maintenance' => 'badge-warning',
                    default       => 'badge-neutral'
                };
                $statusLabel = match($r['status']) {
                    'available'   => 'Trống',
                    'full'        => 'Đầy',
                    'maintenance' => 'Bảo trì',
                    default       => $r['status']
                };
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['room_number']) ?></strong></td>
                <td>Tầng <?= (int)$r['floor'] ?></td>
                <td><?= htmlspecialchars($r['room_type']) ?></td>
                <td><?= (int)$r['capacity'] ?> giường</td>
                <td><strong><?= (int)$r['current_occupants'] ?></strong> người</td>
                <td><strong><?= number_format((float)$r['price_per_month']) ?>đ</strong></td>
                <td><span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7">
                <div class="empty-state" style="padding: 30px;">
                  <div class="empty-icon">🚪</div>
                  <div class="empty-title">Chưa có phòng nào thuộc tòa nhà này</div>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
