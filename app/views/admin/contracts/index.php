<?php
/**
 * app/views/admin/contracts/index.php
 * Admin — Danh sách hợp đồng
 * Variables: $title, $contracts[], $pagination[], $search
 */

$currentPage = (int)($pagination['current_page'] ?? 1);
$lastPage    = (int)($pagination['last_page']    ?? 1);
$total       = (int)($pagination['total']        ?? 0);
$from        = (int)($pagination['from']         ?? 0);
$to          = (int)($pagination['to']           ?? 0);
$searchQ     = htmlspecialchars($search ?? '');
?>

<div class="page-header">
  <div>
    <h1 class="page-title">📄 Quản lý hợp đồng</h1>
    <p class="page-subtitle">Tổng cộng <?= number_format($total) ?> hợp đồng trong hệ thống</p>
  </div>
</div>

<div class="card">
  <!-- Search Filter bar -->
  <div class="filter-bar">
    <div class="filter-search">
      <span class="search-icon">🔍</span>
      <input type="text" 
             id="contractSearch" 
             class="form-control" 
             placeholder="Tìm tên sinh viên, số phòng..." 
             value="<?= $searchQ ?>"
             onkeydown="if(event.key==='Enter') doSearch()">
    </div>
    <button class="btn btn-primary btn-sm" onclick="doSearch()">Tìm kiếm</button>
    <?php if ($searchQ): ?>
      <a href="/testfinal/public/admin/contracts" class="btn btn-ghost btn-sm">✕ Xóa bộ lọc</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:12px;color:var(--txt-muted);">
      Hiển thị <?= $from ?>–<?= $to ?> / <?= number_format($total) ?>
    </span>
  </div>

  <!-- Table -->
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Sinh viên</th>
          <th>Phòng ở</th>
          <th>Bắt đầu</th>
          <th>Kết thúc</th>
          <th>Giá phòng</th>
          <th>Trạng thái</th>
          <th style="width:180px;text-align:center;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($contracts)): ?>
          <?php foreach ($contracts as $c): ?>
            <?php 
              $status = $c['status'] ?? 'active';
              $badge = match($status) {
                  'active'    => ['badge-success', 'Đang hoạt động'],
                  'expired'   => ['badge-neutral', 'Hết hạn'],
                  'cancelled' => ['badge-danger',  'Đã hủy'],
                  default     => ['badge-neutral', $status]
              };
              $initial = mb_strtoupper(mb_substr($c['student_name'] ?? 'S', 0, 1));
            ?>
            <tr id="contract-row-<?= (int)$c['id'] ?>">
              <td>
                <div style="display:flex;align-items:center;gap:9px;">
                  <div class="avatar avatar-sm"><?= $initial ?></div>
                  <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($c['student_name']) ?></div>
                    <div class="sub">MSV: <?= htmlspecialchars($c['student_code']) ?></div>
                  </div>
                </div>
              </td>
              <td><strong>🚪 Tòa <?= htmlspecialchars($c['building_name']) ?> - Phòng <?= htmlspecialchars($c['room_number']) ?></strong></td>
              <td style="color:var(--txt-secondary);"><?= date('d/m/Y', strtotime($c['start_date'])) ?></td>
              <td style="color:var(--txt-secondary);"><?= date('d/m/Y', strtotime($c['end_date'])) ?></td>
              <td style="font-weight:600;color:var(--txt-primary);"><?= number_format((float)$c['monthly_fee']) ?>đ</td>
              <td><span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span></td>
              <td>
                <div style="display:flex;gap:6px;justify-content:center;">
                  <a href="/testfinal/public/admin/contracts/<?= (int)$c['id'] ?>" class="btn btn-ghost btn-sm">👁️ Chi tiết</a>
                  <?php if ($status === 'active'): ?>
                    <button class="btn btn-danger-outline btn-sm" onclick="terminateContract(<?= (int)$c['id'] ?>, '<?= htmlspecialchars(addslashes($c['student_name'])) ?>')">🛑 Chấm dứt</button>
                  <?php endif; ?>
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
                <div class="empty-msg">Các hợp đồng được tạo tự động khi duyệt đơn đăng ký phòng ở.</div>
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
          <a href="?page=<?= $currentPage - 1 ?>&q=<?= $searchQ ?>" class="page-link">‹</a>
        <?php endif; ?>
        <?php for ($p = 1; $p <= $lastPage; $p++): ?>
          <a href="?page=<?= $p ?>&q=<?= $searchQ ?>" class="page-link <?= $p === $currentPage ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($currentPage < $lastPage): ?>
          <a href="?page=<?= $currentPage + 1 ?>&q=<?= $searchQ ?>" class="page-link">›</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function doSearch() {
    const q = document.getElementById('contractSearch').value;
    window.location.href = '?q=' + encodeURIComponent(q) + '&page=1';
}

function terminateContract(id, name) {
    if (!confirm('Bạn có chắc chắn muốn chấm dứt hợp đồng của sinh viên "' + name + '"?\nHành động này sẽ giải phóng chỗ trống trong phòng!')) return;
    
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch('/testfinal/public/admin/contracts/' + id + '/terminate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': token
        }
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            alert('✅ Đã chấm dứt hợp đồng thành công!');
            location.reload();
        } else {
            alert('❌ Lỗi: ' + json.message);
        }
    })
    .catch(err => alert('Lỗi kết nối: ' + err.message));
}
</script>
