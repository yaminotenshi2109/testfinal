<?php
/**
 * app/views/admin/buildings/edit.php
 * Admin — Chỉnh sửa tòa nhà
 * Variables: $title, $building, $_csrfToken, $_errors, $_old
 */

$id      = (int)$building['id'];
$name    = htmlspecialchars($building['name']);
$floors  = (int)$building['total_floors'];
$gender  = htmlspecialchars($building['gender_type']);
$mName   = htmlspecialchars($building['manager_name']);
$mPhone  = htmlspecialchars($building['manager_phone']);
$address = htmlspecialchars($building['address']);
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🏢 Chỉnh sửa tòa nhà</h1>
    <p class="page-subtitle">Cập nhật thông tin chi tiết cho tòa nhà <?= $name ?></p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/buildings" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
  <form method="POST" action="/testfinal/public/admin/buildings/<?= $id ?>">
    <input type="hidden" name="_method" value="PUT">
    <div class="card-body" style="display: flex; flex-direction: column; gap: 16px;">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

      <div class="form-group">
        <label class="form-label">Tên tòa nhà <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_old['name'] ?? $name) ?>" required>
        <?php if (!empty($_errors['name'])): ?>
          <div class="form-error"><?= htmlspecialchars($_errors['name'][0]) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Số lượng tầng <span class="req">*</span></label>
          <input type="number" name="total_floors" class="form-control" value="<?= htmlspecialchars($_old['total_floors'] ?? $floors) ?>" min="1" required>
          <?php if (!empty($_errors['total_floors'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['total_floors'][0]) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Đối tượng giới tính <span class="req">*</span></label>
          <select name="gender_type" class="form-control" required>
            <option value="male" <?= ($_old['gender_type'] ?? $gender) === 'male' ? 'selected' : '' ?>>Nam</option>
            <option value="female" <?= ($_old['gender_type'] ?? $gender) === 'female' ? 'selected' : '' ?>>Nữ</option>
            <option value="mixed" <?= ($_old['gender_type'] ?? $gender) === 'mixed' ? 'selected' : '' ?>>Hỗn hợp (Cả Nam & Nữ)</option>
          </select>
          <?php if (!empty($_errors['gender_type'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['gender_type'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Họ và tên Quản lý <span class="req">*</span></label>
          <input type="text" name="manager_name" class="form-control" value="<?= htmlspecialchars($_old['manager_name'] ?? $mName) ?>" required>
          <?php if (!empty($_errors['manager_name'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['manager_name'][0]) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">SĐT Quản lý <span class="req">*</span></label>
          <input type="text" name="manager_phone" class="form-control" value="<?= htmlspecialchars($_old['manager_phone'] ?? $mPhone) ?>" required>
          <?php if (!empty($_errors['manager_phone'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['manager_phone'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Địa chỉ tòa nhà <span class="req">*</span></label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($_old['address'] ?? $address) ?>" required>
        <?php if (!empty($_errors['address'])): ?>
          <div class="form-error"><?= htmlspecialchars($_errors['address'][0]) ?></div>
        <?php endif; ?>
      </div>

    </div>
    <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
      <a href="/testfinal/public/admin/buildings" class="btn btn-ghost">Hủy</a>
      <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
    </div>
  </form>
</div>
