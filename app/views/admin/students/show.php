<?php
/**
 * app/views/admin/students/show.php
 * Admin — Chi tiết sinh viên
 * Variables: $title, $student, $contracts[], $violations[]
 */

$name     = htmlspecialchars($student['full_name']);
$code     = htmlspecialchars($student['student_code']);
$gender   = $student['gender'] === 'male' ? 'Nam' : ($student['gender'] === 'female' ? 'Nữ' : 'Khác');
$dob      = htmlspecialchars($student['dob']);
$faculty  = htmlspecialchars($student['faculty']);
$program  = htmlspecialchars($student['program']);
$phone    = htmlspecialchars($student['phone']);
$hometown = htmlspecialchars($student['hometown']);
$idCard   = htmlspecialchars($student['id_card']);
$priority = (int)$student['priority_level'];

$priorities = [
    0 => 'Thường',
    1 => 'Chính sách⭐',
    2 => 'Ưu tiên cao⭐⭐'
];
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🎓 Chi tiết sinh viên</h1>
    <p class="page-subtitle">Hồ sơ cá nhân và lịch sử hoạt động của <?= $name ?></p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/students" class="btn btn-ghost btn-sm">← Quay lại danh sách</a>
  </div>
</div>

<div class="grid-2" style="grid-template-columns: 1fr 2fr; gap: 24px;">
  <!-- Left Column: Personal info card -->
  <div class="card" style="padding: 24px;">
    <div style="text-align: center; margin-bottom: 24px;">
      <div class="avatar" style="width: 80px; height: 80px; font-size: 28px; font-weight: 800; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; border: 3px solid var(--border);">
        <?= mb_strtoupper(mb_substr($name, 0, 1)) ?>
      </div>
      <h3 style="font-size: 16px; font-weight: 700; color: var(--txt-primary); margin-bottom: 4px;"><?= $name ?></h3>
      <p style="color: var(--txt-muted); font-size: 13px; margin: 0;">Mã SV: <?= $code ?></p>
    </div>

    <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13.5px; border-top: 1px solid var(--border); padding-top: 16px;">
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">Giới tính:</span>
        <strong style="color: var(--txt-primary);"><?= $gender ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">Ngày sinh:</span>
        <strong style="color: var(--txt-primary);"><?= date('d/m/Y', strtotime($dob)) ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">Khoa/Viện:</span>
        <strong style="color: var(--txt-primary);"><?= $faculty ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">Hệ đào tạo:</span>
        <strong style="color: var(--txt-primary);"><?= $program ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">Diện ưu tiên:</span>
        <span class="badge badge-info"><?= $priorities[$priority] ?? 'Thường' ?></span>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">CCCD/CMND:</span>
        <strong style="color: var(--txt-primary);"><?= $idCard ?></strong>
      </div>
      <div style="display: flex; justify-content: space-between;">
        <span style="color: var(--txt-muted);">Số điện thoại:</span>
        <strong style="color: var(--txt-primary);"><?= $phone ?></strong>
      </div>
      <div style="display: flex; flex-direction: column; gap: 4px;">
        <span style="color: var(--txt-muted);">Quê quán:</span>
        <strong style="color: var(--txt-primary); line-height: 1.4;"><?= $hometown ?></strong>
      </div>
    </div>
  </div>

  <!-- Right Column: Tabs (Contracts + Violations) -->
  <div style="display: flex; flex-direction: column; gap: 24px;">
    
    <!-- Lịch sử hợp đồng -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">📄 Lịch sử hợp đồng thuê phòng</h3>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Phòng</th>
              <th>Thời hạn</th>
              <th>Đơn giá</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($contracts)): ?>
              <?php foreach ($contracts as $c): ?>
                <?php 
                  $cStatus = $c['status'] ?? '';
                  $badge = match($cStatus) {
                      'active'    => ['badge-success', 'Đang ở'],
                      'expired'   => ['badge-neutral', 'Hết hạn'],
                      'cancelled' => ['badge-danger',  'Đã hủy'],
                      default     => ['badge-neutral', $cStatus]
                  };
                ?>
                <tr>
                  <td><strong><?= htmlspecialchars($c['building_name']) ?> - <?= htmlspecialchars($c['room_number']) ?></strong></td>
                  <td style="font-size:12.5px;color:var(--txt-secondary);">
                    <?= date('d/m/Y', strtotime($c['start_date'])) ?> – <?= date('d/m/Y', strtotime($c['end_date'])) ?>
                  </td>
                  <td style="font-weight:600;color:var(--txt-primary);"><?= number_format((float)$c['monthly_fee']) ?>đ</td>
                  <td><span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4">
                  <div class="empty-state" style="padding: 24px;">
                    <div class="empty-icon">📄</div>
                    <div class="empty-title" style="font-size:13.5px;">Chưa có lịch sử hợp đồng</div>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Lịch sử vi phạm -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">⚠️ Lịch sử vi phạm</h3>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Loại vi phạm</th>
              <th>Mô tả</th>
              <th>Điểm trừ</th>
              <th>Ngày ghi nhận</th>
              <th>Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($violations)): ?>
              <?php foreach ($violations as $v): ?>
                <?php 
                  $vStatus = $v['status'] ?? 'active';
                  $badge = match($vStatus) {
                      'active'    => ['badge-danger', 'Vi phạm'],
                      'appealed'  => ['badge-warning', 'Đang khiếu nại'],
                      'dismissed' => ['badge-neutral', 'Đã bãi bỏ'],
                      default     => ['badge-neutral', $vStatus]
                  };
                ?>
                <tr>
                  <td><strong><?= htmlspecialchars($v['violation_type']) ?></strong></td>
                  <td style="font-size:12.5px;color:var(--txt-secondary);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($v['description']) ?>">
                    <?= htmlspecialchars($v['description']) ?>
                  </td>
                  <td style="font-weight:700;color:var(--danger);">⚠️ -<?= (int)$v['penalty_points'] ?>đ</td>
                  <td style="font-size:12px;color:var(--txt-muted);"><?= date('d/m/Y', strtotime($v['recorded_at'])) ?></td>
                  <td><span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="5">
                  <div class="empty-state" style="padding: 24px;">
                    <div class="empty-icon">🎉</div>
                    <div class="empty-title" style="font-size:13.5px;">Tuyệt vời! Không có lịch sử vi phạm nào</div>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
