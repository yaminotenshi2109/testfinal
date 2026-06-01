<?php
/**
 * app/views/admin/buildings/create.php
 * Admin — Thêm tòa nhà
 * Variables: $title, $_csrfToken, $_errors, $_old
 */
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🏢 Thêm tòa nhà mới</h1>
    <p class="page-subtitle">Tạo cơ sở vật chất mới cho hệ thống ký túc xá</p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/buildings" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
  <form method="POST" action="/testfinal/public/admin/buildings">
    <div class="card-body" style="display: flex; flex-direction: column; gap: 16px;">
      <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

      <div class="form-group">
        <label class="form-label">Tên tòa nhà <span class="req">*</span></label>
        <input type="text" name="name" class="form-control" placeholder="Ví dụ: Tòa A1, Tòa B..." value="<?= htmlspecialchars($_old['name'] ?? '') ?>" required>
        <?php if (!empty($_errors['name'])): ?>
          <div class="form-error"><?= htmlspecialchars($_errors['name'][0]) ?></div>
        <?php endif; ?>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Số lượng tầng <span class="req">*</span></label>
          <input type="number" name="total_floors" class="form-control" placeholder="Ví dụ: 5" value="<?= htmlspecialchars($_old['total_floors'] ?? '1') ?>" min="1" required>
          <?php if (!empty($_errors['total_floors'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['total_floors'][0]) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Đối tượng giới tính <span class="req">*</span></label>
          <select name="gender_type" class="form-control" required>
            <option value="male" <?= ($_old['gender_type'] ?? '') === 'male' ? 'selected' : '' ?>>Nam</option>
            <option value="female" <?= ($_old['gender_type'] ?? '') === 'female' ? 'selected' : '' ?>>Nữ</option>
            <option value="mixed" <?= ($_old['gender_type'] ?? '') === 'mixed' ? 'selected' : '' ?>>Hỗn hợp (Cả Nam & Nữ)</option>
          </select>
          <?php if (!empty($_errors['gender_type'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['gender_type'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Họ và tên Quản lý <span class="req">*</span></label>
          <input type="text" name="manager_name" class="form-control" placeholder="Ví dụ: Nguyễn Văn Quản" value="<?= htmlspecialchars($_old['manager_name'] ?? '') ?>" required>
          <?php if (!empty($_errors['manager_name'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['manager_name'][0]) ?></div>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">SĐT Quản lý <span class="req">*</span></label>
          <input type="text" name="manager_phone" class="form-control" placeholder="Ví dụ: 0987654321" value="<?= htmlspecialchars($_old['manager_phone'] ?? '') ?>" required>
          <?php if (!empty($_errors['manager_phone'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['manager_phone'][0]) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Địa chỉ tòa nhà <span class="req">*</span></label>
        <input type="text" name="address" class="form-control" placeholder="Ví dụ: Khu A, Ký túc xá trung tâm..." value="<?= htmlspecialchars($_old['address'] ?? '') ?>" required>
        <?php if (!empty($_errors['address'])): ?>
          <div class="form-error"><?= htmlspecialchars($_errors['address'][0]) ?></div>
        <?php endif; ?>
      </div>

    </div>
    <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
      <a href="/testfinal/public/admin/buildings" class="btn btn-ghost">Hủy</a>
      <button type="submit" class="btn btn-primary">➕ Tạo tòa nhà</button>
    </div>
  </form>
</div>
