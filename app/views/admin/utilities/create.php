<?php
/**
 * app/views/admin/utilities/create.php
 * Admin — Ghi chỉ số điện nước mới
 * Variables: $title, $rooms[], $_csrfToken, $_errors, $_old
 */

$currentMonth = (int)date('m');
$currentYear  = (int)date('Y');
?>

<div class="page-header">
  <div>
    <h1 class="page-title">⚡ Ghi chỉ số điện nước mới</h1>
    <p class="page-subtitle">Nhập số công tơ điện nước tiêu thụ của phòng</p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/utilities" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="card" style="max-width: 650px; margin: 0 auto;">
  <form method="POST" action="/testfinal/public/admin/utilities">
    <div class="card-body" style="display: flex; flex-direction: column; gap: 16px;">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Chọn phòng <span class="req">*</span></label>
          <select name="room_id" class="form-control" required>
            <option value="">-- Chọn phòng --</option>
            <?php foreach ($rooms as $r): ?>
              <option value="<?= (int)$r['id'] ?>" <?= (int)($_old['room_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>Tòa <?= htmlspecialchars($r['building_name']) ?> - Phòng <?= htmlspecialchars($r['room_number']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($_errors['room_id'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['room_id'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Tháng ghi <span class="req">*</span></label>
          <input type="number" name="month" class="form-control" value="<?= htmlspecialchars($_old['month'] ?? $currentMonth) ?>" min="1" max="12" required>
          <?php if (!empty($_errors['month'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['month'][0]) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Năm ghi <span class="req">*</span></label>
          <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($_old['year'] ?? $currentYear) ?>" min="2020" max="2100" required>
          <?php if (!empty($_errors['year'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['year'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <hr style="border:0;border-top:1px solid var(--border);margin:8px 0;">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Chỉ số Điện ĐẦU kỳ <span class="req">*</span></label>
          <input type="number" name="elec_prev" class="form-control" placeholder="Ví dụ: 120.5" step="0.1" value="<?= htmlspecialchars($_old['elec_prev'] ?? '0.0') ?>" required>
          <?php if (!empty($_errors['elec_prev'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['elec_prev'][0]) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Chỉ số Điện CUỐI kỳ <span class="req">*</span></label>
          <input type="number" name="elec_curr" class="form-control" placeholder="Ví dụ: 250.2" step="0.1" value="<?= htmlspecialchars($_old['elec_curr'] ?? '') ?>" required>
          <?php if (!empty($_errors['elec_curr'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['elec_curr'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Chỉ số Nước ĐẦU kỳ <span class="req">*</span></label>
          <input type="number" name="water_prev" class="form-control" placeholder="Ví dụ: 15.0" step="0.1" value="<?= htmlspecialchars($_old['water_prev'] ?? '0.0') ?>" required>
          <?php if (!empty($_errors['water_prev'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['water_prev'][0]) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Chỉ số Nước CUỐI kỳ <span class="req">*</span></label>
          <input type="number" name="water_curr" class="form-control" placeholder="Ví dụ: 30.5" step="0.1" value="<?= htmlspecialchars($_old['water_curr'] ?? '') ?>" required>
          <?php if (!empty($_errors['water_curr'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['water_curr'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Đơn giá Điện (VND/kWh)</label>
          <input type="number" name="elec_rate" class="form-control" value="<?= htmlspecialchars($_old['elec_rate'] ?? '3500') ?>" placeholder="3500">
        </div>
        <div class="form-group">
          <label class="form-label">Đơn giá Nước (VND/m³)</label>
          <input type="number" name="water_rate" class="form-control" value="<?= htmlspecialchars($_old['water_rate'] ?? '15000') ?>" placeholder="15000">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Ghi chú</label>
        <input type="text" name="notes" class="form-control" placeholder="Nhập ghi chú nếu có..." value="<?= htmlspecialchars($_old['notes'] ?? '') ?>">
      </div>

    </div>
    <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
      <a href="/testfinal/public/admin/utilities" class="btn btn-ghost">Hủy</a>
      <button type="submit" class="btn btn-primary">➕ Ghi chỉ số</button>
    </div>
  </form>
</div>
