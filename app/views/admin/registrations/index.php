<?php
/**
 * Admin – Danh sách đăng ký ở KTX
 * Variables: $title, $registrations (array), $pagination, $status, $semester
 */

$filterStatus   = $status   ?? ($_GET['status']   ?? '');
$filterSemester = $semester ?? ($_GET['semester']  ?? '');
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">📋 <?= htmlspecialchars($title ?? 'Quản lý đăng ký ở KTX') ?></h1>
        <p class="page-subtitle">Duyệt và quản lý hồ sơ đăng ký ký túc xá của sinh viên</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="/testfinal/public/admin/registrations" class="filter-bar-form">
        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="pending"   <?= $filterStatus === 'pending'   ? 'selected' : '' ?>>Chờ duyệt</option>
                <option value="approved"  <?= $filterStatus === 'approved'  ? 'selected' : '' ?>>Đã duyệt</option>
                <option value="rejected"  <?= $filterStatus === 'rejected'  ? 'selected' : '' ?>>Đã từ chối</option>
                <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
            </select>
        </div>
        <div class="filter-group">
            <select name="semester" class="form-control">
                <option value="">-- Tất cả học kỳ --</option>
                <option value="1" <?= $filterSemester === '1' ? 'selected' : '' ?>>Học kỳ 1</option>
                <option value="2" <?= $filterSemester === '2' ? 'selected' : '' ?>>Học kỳ 2</option>
                <option value="3" <?= $filterSemester === '3' ? 'selected' : '' ?>>Học kỳ hè</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="/testfinal/public/admin/registrations" class="btn btn-outline">Đặt lại</a>
    </form>
</div>

<!-- Registrations Table -->
<div class="card">
    <div class="card-body" style="padding:0">
        <?php if (!empty($registrations)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Sinh viên</th>
                            <th style="text-align:center">Giới tính</th>
                            <th>Tòa ưu tiên</th>
                            <th style="text-align:center">Học kỳ</th>
                            <th>Phòng được gán</th>
                            <th style="text-align:center">Trạng thái</th>
                            <th>Ngày đăng ký</th>
                            <th style="text-align:center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $page    = $pagination['current_page'] ?? 1;
                        $perPage = $pagination['per_page'] ?? 20;
                        $offset  = ($page - 1) * $perPage;

                        foreach ($registrations as $i => $reg):
                            $statusMap = [
                                'pending'   => ['label' => 'Chờ duyệt', 'class' => 'badge-warning'],
                                'approved'  => ['label' => 'Đã duyệt',  'class' => 'badge-success'],
                                'rejected'  => ['label' => 'Từ chối',   'class' => 'badge-danger'],
                                'cancelled' => ['label' => 'Đã hủy',    'class' => 'badge-neutral'],
                            ];
                            $st = $statusMap[$reg['status'] ?? ''] ?? ['label' => $reg['status'] ?? '—', 'class' => 'badge-neutral'];

                            $genderLabel = match($reg['gender'] ?? '') {
                                'male'   => '♂ Nam',
                                'female' => '♀ Nữ',
                                default  => '—',
                            };

                            $priorityMap = [
                                0 => 'Thường',
                                1 => 'Chính sách ⭐',
                                2 => 'Ưu tiên cao ⭐⭐',
                            ];
                            $priorityLabel = $priorityMap[(int)($reg['priority_level'] ?? 0)] ?? 'Thường';
                        ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($reg['full_name'] ?? '—') ?></div>
                                <div style="font-size:0.78rem;color:var(--text-muted)"><?= $priorityLabel ?></div>
                            </td>
                            <td style="text-align:center"><?= $genderLabel ?></td>
                            <td><?= htmlspecialchars($reg['building_name'] ?? '—') ?></td>
                            <td style="text-align:center">
                                <?php if (!empty($reg['semester'])): ?>
                                    HK<?= htmlspecialchars($reg['semester']) ?>
                                    <?= !empty($reg['academic_year']) ? ' - ' . htmlspecialchars($reg['academic_year']) : '' ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($reg['room_number'])): ?>
                                    <span style="font-weight:600"><?= htmlspecialchars($reg['room_number']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);font-style:italic">Chưa gán</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center">
                                <span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                            </td>
                            <td>
                                <?php
                                $dt = $reg['created_at'] ?? '';
                                echo $dt ? htmlspecialchars(date('d/m/Y', strtotime($dt))) : '—';
                                ?>
                            </td>
                            <td style="text-align:center">
                                <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                                    <a href="/testfinal/public/admin/registrations/<?= (int)$reg['id'] ?>"
                                       class="btn btn-ghost btn-sm">👁 Xem</a>

                                    <?php if (($reg['status'] ?? '') === 'pending'): ?>
                                        <!-- Approve -->
                                        <form method="POST"
                                              action="/testfinal/public/admin/registrations/<?= (int)$reg['id'] ?>/approve"
                                              onsubmit="return confirm('Xác nhận duyệt đăng ký này?')"
                                              style="display:inline">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">✅ Duyệt</button>
                                        </form>

                                        <!-- Reject (open modal) -->
                                        <button
                                            class="btn btn-danger btn-sm"
                                            onclick="openRejectModal(<?= (int)$reg['id'] ?>)">
                                            ❌ Từ chối
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <div class="empty-state-title">Không có đăng ký nào</div>
                <div class="empty-state-desc">Không tìm thấy hồ sơ đăng ký phù hợp với bộ lọc hiện tại.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if (!empty($pagination) && ($pagination['total_pages'] ?? 1) > 1): ?>
    <div class="pagination">
        <?php
        $cur   = (int)($pagination['current_page'] ?? 1);
        $total = (int)($pagination['total_pages'] ?? 1);
        $qs    = http_build_query(array_filter([
            'status'   => $filterStatus,
            'semester' => $filterSemester,
        ]));
        ?>
        <?php if ($cur > 1): ?>
            <a href="/testfinal/public/admin/registrations?page=<?= $cur - 1 ?>&<?= $qs ?>" class="page-link">‹ Trước</a>
        <?php endif; ?>
        <?php for ($p = max(1, $cur - 2); $p <= min($total, $cur + 2); $p++): ?>
            <a href="/testfinal/public/admin/registrations?page=<?= $p ?>&<?= $qs ?>"
               class="page-link <?= $p === $cur ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($cur < $total): ?>
            <a href="/testfinal/public/admin/registrations?page=<?= $cur + 1 ?>&<?= $qs ?>" class="page-link">Sau ›</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Modal: Từ chối đăng ký -->
<div class="modal-overlay" id="modal-reject-registration" style="display:none" onclick="closeRejectModal()">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <h3 class="card-title">❌ Từ chối đăng ký</h3>
                <button class="btn btn-ghost btn-sm" onclick="closeRejectModal()">✕</button>
            </div>
            <div class="card-body">
                <form id="form-reject-registration" method="POST" action="">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
                    <div class="form-group">
                        <label class="form-label" for="reject-reason">
                            Lý do từ chối <span style="color:#ef4444">*</span>
                        </label>
                        <textarea
                            id="reject-reason"
                            name="reason"
                            class="form-control"
                            rows="4"
                            placeholder="Nhập lý do từ chối hồ sơ đăng ký này..."
                            required
                        ></textarea>
                        <div class="form-error" id="reject-reason-error" style="display:none">
                            Vui lòng nhập lý do từ chối.
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button class="btn btn-outline" onclick="closeRejectModal()">Hủy</button>
                <button class="btn btn-danger" onclick="submitReject()">❌ Xác nhận từ chối</button>
            </div>
        </div>
    </div>
</div>

<script>
function openRejectModal(registrationId) {
    var modal  = document.getElementById('modal-reject-registration');
    var form   = document.getElementById('form-reject-registration');
    var err    = document.getElementById('reject-reason-error');
    var reason = document.getElementById('reject-reason');

    form.action = '/testfinal/public/admin/registrations/' + registrationId + '/reject';
    reason.value = '';
    err.style.display = 'none';
    modal.style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('modal-reject-registration').style.display = 'none';
}

function submitReject() {
    var reason = document.getElementById('reject-reason');
    var err    = document.getElementById('reject-reason-error');

    if (!reason.value.trim()) {
        err.style.display = 'block';
        reason.focus();
        return;
    }
    err.style.display = 'none';

    if (confirm('Xác nhận từ chối hồ sơ đăng ký này?')) {
        document.getElementById('form-reject-registration').submit();
    }
}
</script>
