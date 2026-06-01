<?php
/**
 * app/views/admin/utilities/edit.php
 * Admin — Chỉnh sửa chỉ số điện nước
 * Variables: $title, $reading, $_csrfToken, $_errors, $_old
 */

$id        = (int)$reading['id'];
$room      = htmlspecialchars($reading['building_name'] . ' - ' . $reading['room_number']);
$month     = (int)$reading['month'];
$year      = (int)$reading['year'];
$elecPrev  = (float)$reading['elec_prev'];
$elecCurr  = (float)$reading['elec_curr'];
$waterPrev = (float)$reading['water_prev'];
$waterCurr = (float)$reading['water_curr'];
$elecRate  = (float)$reading['elec_rate'];
$waterRate = (float)$reading['water_rate'];
$notes     = htmlspecialchars($reading['notes'] ?? '');
?>

<div class="page-header">
  <div>
    <h1 class="page-title">⚡ Chỉnh sửa chỉ số điện nước</h1>
    <p class="page-subtitle">Sửa chỉ số tiêu thụ Phòng <?= $room ?> (Tháng <?= $month ?>/<?= $year ?>)</p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/utilities" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="card" style="max-width: 650px; margin: 0 auto;">
  <form method="POST" action="/testfinal/public/admin/utilities/<?= $id ?>">
    <input type="hidden" name="_method" value="PUT">
    <div class="card-body" style="display: flex; flex-direction: column; gap: 16px;">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Phòng ở</label>
          <input type="text" class="form-control" value="<?= $room ?>" disabled style="background:var(--bg-neutral);cursor:not-allowed;">
        </div>
        <div class="form-group">
          <label class="form-label">Thời hạn ghi</label>
          <input type="text" class="form-control" value="Tháng <?= $month ?> / <?= $year ?>" disabled style="background:var(--bg-neutral);cursor:not-allowed;">
        </div>
      </div>

      <hr style="border:0;border-top:1px solid var(--border);margin:8px 0;">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Chỉ số Điện ĐẦU kỳ <span class="req">*</span></label>
          <input type="number" name="elec_prev" class="form-control" step="0.1" value="<?= htmlspecialchars($_old['elec_prev'] ?? $elecPrev) ?>" required>
          <?php if (!empty($_errors['elec_prev'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['elec_prev'][0]) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Chỉ số Điện CUỐI kỳ <span class="req">*</span></label>
          <input type="number" name="elec_curr" class="form-control" step="0.1" value="<?= htmlspecialchars($_old['elec_curr'] ?? $elecCurr) ?>" required>
          <?php if (!empty($_errors['elec_curr'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['elec_curr'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Chỉ số Nước ĐẦU kỳ <span class="req">*</span></label>
          <input type="number" name="water_prev" class="form-control" step="0.1" value="<?= htmlspecialchars($_old['water_prev'] ?? $waterPrev) ?>" required>
          <?php if (!empty($_errors['water_prev'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['water_prev'][0]) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Chỉ số Nước CUỐI kỳ <span class="req">*</span></label>
          <input type="number" name="water_curr" class="form-control" step="0.1" value="<?= htmlspecialchars($_old['water_curr'] ?? $waterCurr) ?>" required>
          <?php if (!empty($_errors['water_curr'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['water_curr'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Đơn giá Điện (VND/kWh)</label>
          <input type="number" name="elec_rate" class="form-control" value="<?= htmlspecialchars($_old['elec_rate'] ?? $elecRate) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Đơn giá Nước (VND/m³)</label>
          <input type="number" name="water_rate" class="form-control" value="<?= htmlspecialchars($_old['water_rate'] ?? $waterRate) ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Ghi chú</label>
        <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars($_old['notes'] ?? $notes) ?>">
      </div>

    </div>
    <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
      <a href="/testfinal/public/admin/utilities" class="btn btn-ghost">Hủy</a>
      <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
    </div>
  </form>
</div>
