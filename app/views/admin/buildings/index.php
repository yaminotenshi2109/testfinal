<?php
/**
 * app/views/admin/buildings/index.php
 * Admin — Danh sách tòa nhà
 * Variables: $title, $buildings[]
 */
?>

<div class="page-header">
  <div>
    <h1 class="page-title">🏢 Quản lý tòa nhà</h1>
    <p class="page-subtitle">Quản lý cơ cấu các tòa nhà ký túc xá</p>
  </div>
  <div class="page-actions">
    <a href="/testfinal/public/admin/buildings/create" class="btn btn-primary">➕ Thêm tòa nhà</a>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Tên tòa nhà</th>
          <th>Số tầng</th>
          <th>Đối tượng ở</th>
          <th>Số phòng</th>
          <th>Sức chứa (Đang ở / Tổng)</th>
          <th>Tỷ lệ lấp đầy</th>
          <th>Quản lý tòa</th>
          <th style="width:180px;text-align:center;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($buildings)): ?>
          <?php foreach ($buildings as $b): ?>
            <?php 
              $genderLabel = match($b['gender_type']) {
                  'male'   => '🟢 Nam',
                  'female' => '🌸 Nữ',
                  'mixed'  => '⚖️ Hỗn hợp',
                  default  => $b['gender_type']
              };
              
              $totalCapacity  = (int)($b['total_capacity'] ?? 0);
              $totalOccupants = (int)($b['total_occupants'] ?? 0);
              $fillPct        = $totalCapacity > 0 ? round(($totalOccupants / $totalCapacity) * 100) : 0;
              $barClass       = $fillPct >= 90 ? 'danger' : ($fillPct >= 70 ? 'warning' : 'success');
            ?>
            <tr id="building-row-<?= (int)$b['id'] ?>">
              <td><strong style="color:var(--txt-primary);font-size:15px;">🏢 Tòa <?= htmlspecialchars($b['name']) ?></strong></td>
              <td style="color:var(--txt-secondary);"><?= (int)$b['total_floors'] ?> tầng</td>
              <td><?= $genderLabel ?></td>
              <td style="font-weight:600;color:var(--txt-primary);"><?= (int)$b['room_count'] ?> phòng</td>
              <td>
                <span style="font-weight:600;"><?= $totalOccupants ?></span> / <span style="color:var(--txt-muted);"><?= $totalCapacity ?></span>
              </td>
              <td>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="progress" style="width:70px;margin:0;">
                    <div class="progress-bar <?= $barClass ?>" style="width:<?= $fillPct ?>%;"></div>
                  </div>
                  <span style="font-size:12px;font-weight:700;"><?= $fillPct ?>%</span>
                </div>
              </td>
              <td>
                <div><strong><?= htmlspecialchars($b['manager_name']) ?></strong></div>
                <div class="sub" style="font-size:11px;"><?= htmlspecialchars($b['manager_phone']) ?></div>
              </td>
              <td>
                <div style="display:flex;gap:6px;justify-content:center;">
                  <a href="/testfinal/public/admin/buildings/<?= (int)$b['id'] ?>" class="btn btn-ghost btn-sm">👁️ Xem</a>
                  <a href="/testfinal/public/admin/buildings/<?= (int)$b['id'] ?>/edit" class="btn btn-ghost btn-sm">✏️ Sửa</a>
                  <button class="btn btn-danger-outline btn-sm" onclick="deleteBuilding(<?= (int)$b['id'] ?>, '<?= htmlspecialchars(addslashes($b['name'])) ?>')">🗑️ Xóa</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-icon">🏢</div>
                <div class="empty-title">Chưa có tòa nhà nào</div>
                <div class="empty-msg">Bấm nút "Thêm tòa nhà" ở góc trên để tạo mới.</div>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function deleteBuilding(id, name) {
    if (!confirm('Bạn có chắc chắn muốn xóa tòa nhà "' + name + '"?\nHành động này không thể hoàn tác!')) return;
    
    fetch('/testfinal/public/admin/buildings/' + id, {
        method: 'DELETE',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            alert('✅ Đã xóa tòa nhà thành công!');
            const row = document.getElementById('building-row-' + id);
            if (row) row.remove();
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}
</script>
