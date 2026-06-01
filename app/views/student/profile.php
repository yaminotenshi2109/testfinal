<?php
/**
 * app/views/student/profile.php
 * Student Profile View
 * Variables: $title, $student, $_csrfToken, $_errors, $_flash, $_old
 */

$name     = $student['full_name']    ?? 'Sinh viên';
$code     = $student['student_code'] ?? '';
$gender   = $student['gender']      ?? 'male';
$dob      = $student['dob']         ?? '';
$faculty  = $student['faculty']      ?? '';
$program  = $student['program']      ?? '';
$priority = (int)($student['priority_level'] ?? 0);
$phone    = $student['phone']        ?? '';
$hometown = $student['hometown']     ?? '';
$idCard   = $student['id_card']      ?? '';

// Priorities
$priorities = [
    0 => 'Thường',
    1 => 'Chính sách⭐',
    2 => 'Ưu tiên cao⭐⭐'
];

$genderLabel = $gender === 'male' ? 'Nam' : ($gender === 'female' ? 'Nữ' : 'Khác');
?>

<div class="page-header">
  <div>
    <h1 class="page-title">👤 Hồ sơ cá nhân</h1>
    <p class="page-subtitle">Quản lý và cập nhật thông tin cá nhân của bạn</p>
  </div>
</div>

<div class="grid-2" style="grid-template-columns: 1fr 2fr; gap: 24px;">
  <!-- Left Side: Profile Card Summary -->
  <div class="card" style="text-align: center; padding: 32px 24px;">
    <div class="avatar" style="width: 100px; height: 100px; font-size: 36px; font-weight: 800; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #a855f7); color: #fff; display: inline-flex; align-items: center; justify-content: center; border: 4px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 16px;">
      <?= mb_strtoupper(mb_substr($name, 0, 1)) ?>
    </div>
    <h3 style="font-size: 18px; font-weight: 800; color: var(--txt-primary); margin-bottom: 4px;"><?= htmlspecialchars($name) ?></h3>
    <p style="color: var(--txt-muted); font-size: 13px; margin-bottom: 16px;">Mã SV: <?= htmlspecialchars($code) ?></p>
    
    <div style="border-top: 1px solid var(--border); padding-top: 16px; text-align: left; display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-secondary);">Khoa/Viện:</span>
        <strong style="color: var(--txt-primary);"><?= htmlspecialchars($faculty) ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-secondary);">Hệ đào tạo:</span>
        <strong style="color: var(--txt-primary);"><?= htmlspecialchars($program) ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-secondary);">Diện ưu tiên:</span>
        <span class="badge badge-info"><?= $priorities[$priority] ?? 'Thường' ?></span>
      </div>
    </div>
  </div>

  <!-- Right Side: Edit Form -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">📝 Cập nhật thông tin chi tiết</h3>
    </div>
    <form method="POST" action="<?= getDynamicUrl('/student/profile') ?>">
      <div class="card-body" style="display: flex; flex-direction: column; gap: 18px;">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

        <!-- Read-only Fields row -->
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Họ và tên</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($name) ?>" disabled style="background: var(--bg-neutral); cursor: not-allowed;">
            <span class="form-hint">Để đổi tên, vui lòng liên hệ ban quản lý.</span>
          </div>
          <div class="form-group">
            <label class="form-label">Mã số sinh viên</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($code) ?>" disabled style="background: var(--bg-neutral); cursor: not-allowed;">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Giới tính</label>
            <input type="text" class="form-control" value="<?= $genderLabel ?>" disabled style="background: var(--bg-neutral); cursor: not-allowed;">
          </div>
          <div class="form-group">
            <label class="form-label">Ngày sinh</label>
            <input type="text" class="form-control" value="<?= $dob ? date('d/m/Y', strtotime($dob)) : '' ?>" disabled style="background: var(--bg-neutral); cursor: not-allowed;">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Số CCCD / CMND</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($idCard) ?>" disabled style="background: var(--bg-neutral); cursor: not-allowed;">
          </div>
          <div class="form-group">
            <label class="form-label">Số điện thoại <span class="req">*</span></label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_old['phone'] ?? $phone) ?>" placeholder="Ví dụ: 0912345678" required>
            <?php if (!empty($_errors['phone'])): ?>
              <div class="form-error"><?= htmlspecialchars($_errors['phone'][0]) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Quê quán (Địa chỉ thường trú) <span class="req">*</span></label>
          <textarea name="hometown" class="form-control" rows="3" placeholder="Nhập địa chỉ đầy đủ..." required><?= htmlspecialchars($_old['hometown'] ?? $hometown) ?></textarea>
          <?php if (!empty($_errors['hometown'])): ?>
            <div class="form-error"><?= htmlspecialchars($_errors['hometown'][0]) ?></div>
          <?php endif; ?>
        </div>

      </div>
      <div class="card-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
        <a href="<?= getDynamicUrl('/student/dashboard') ?>" class="btn btn-ghost">Quay lại</a>
        <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
      </div>
    </form>
  </div>
</div>
