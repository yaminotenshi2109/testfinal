<?php
/**
 * app/views/admin/students/index.php
 * Admin — Danh sách sinh viên
 * Variables: $title, $students[], $pagination[], $search
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
    <h1 class="page-title">🎓 Quản lý sinh viên</h1>
    <p class="page-subtitle">Tổng cộng <?= number_format($total) ?> hồ sơ sinh viên trong hệ thống</p>
  </div>
</div>

<div class="card">
  <!-- Search/Filter bar -->
  <div class="filter-bar">
    <div class="filter-search">
      <span class="search-icon">🔍</span>
      <input type="text" 
             id="studentSearch" 
             class="form-control" 
             placeholder="Tìm tên, MSV, số điện thoại..." 
             value="<?= $searchQ ?>"
             onkeydown="if(event.key==='Enter') doSearch()">
    </div>
    <button class="btn btn-primary btn-sm" onclick="doSearch()">Tìm kiếm</button>
    <?php if ($searchQ): ?>
      <a href="/testfinal/public/admin/students" class="btn btn-ghost btn-sm">✕ Xóa bộ lọc</a>
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
          <th>Mã SV</th>
          <th>Họ và tên</th>
          <th>Giới tính</th>
          <th>Khoa</th>
          <th>Số điện thoại</th>
          <th>Phòng ở</th>
          <th>Trạng thái TK</th>
          <th style="width:80px;text-align:center;">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($students)): ?>
          <?php foreach ($students as $s): ?>
            <?php 
              $genderLabel = $s['gender'] === 'male' ? 'Nam' : ($s['gender'] === 'female' ? 'Nữ' : 'Khác');
              $initial = mb_strtoupper(mb_substr($s['full_name'] ?? 'S', 0, 1));
            ?>
            <tr>
              <td><strong style="color:var(--txt-primary);"><?= htmlspecialchars($s['student_code']) ?></strong></td>
              <td>
                <div style="display:flex;align-items:center;gap:9px;">
                  <div class="avatar avatar-sm"><?= $initial ?></div>
                  <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($s['full_name']) ?></div>
                    <div class="sub"><?= htmlspecialchars($s['email']) ?></div>
                  </div>
                </div>
              </td>
              <td style="color:var(--txt-secondary);"><?= $genderLabel ?></td>
              <td style="color:var(--txt-secondary);"><?= htmlspecialchars($s['faculty']) ?></td>
              <td style="color:var(--txt-secondary);"><?= htmlspecialchars($s['phone']) ?></td>
              <td>
                <?php if (!empty($s['room_number'])): ?>
                  <span class="badge badge-info">🚪 <?= htmlspecialchars($s['building_name']) ?> - <?= htmlspecialchars($s['room_number']) ?></span>
                <?php else: ?>
                  <span class="badge badge-neutral">Chưa xếp phòng</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($s['user_status'] === 'active'): ?>
                  <span class="badge badge-success">✅ Hoạt động</span>
                <?php else: ?>
                  <span class="badge badge-danger">🔴 Khóa</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex;justify-content:center;">
                  <a href="/testfinal/public/admin/students/<?= (int)$s['id'] ?>" class="btn btn-ghost btn-sm" title="Chi tiết">👁️ Chi tiết</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-icon">🎓</div>
                <div class="empty-title">Không tìm thấy sinh viên</div>
                <div class="empty-msg">Thử tìm kiếm với từ khóa khác hoặc chưa có sinh viên nào.</div>
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
    const q = document.getElementById('studentSearch').value;
    window.location.href = '?q=' + encodeURIComponent(q) + '&page=1';
}
</script>
