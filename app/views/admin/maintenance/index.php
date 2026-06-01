<?php
/**
 * Admin – Danh sách yêu cầu bảo trì
 * Variables: $title, $requests (array), $pagination
 */

$filterStatus   = $_GET['status']   ?? '';
$filterPriority = $_GET['priority'] ?? '';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">🔧 <?= htmlspecialchars($title ?? 'Quản lý bảo trì') ?></h1>
        <p class="page-subtitle">Xử lý các yêu cầu sửa chữa và bảo trì cơ sở vật chất ký túc xá</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="/testfinal/public/admin/maintenance" class="filter-bar-form">
        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="open"        <?= $filterStatus === 'open'        ? 'selected' : '' ?>>Mới mở</option>
                <option value="in_progress" <?= $filterStatus === 'in_progress' ? 'selected' : '' ?>>Đang xử lý</option>
                <option value="resolved"    <?= $filterStatus === 'resolved'    ? 'selected' : '' ?>>Đã giải quyết</option>
                <option value="closed"      <?= $filterStatus === 'closed'      ? 'selected' : '' ?>>Đã đóng</option>
                <option value="rejected"    <?= $filterStatus === 'rejected'    ? 'selected' : '' ?>>Đã từ chối</option>
            </select>
        </div>
        <div class="filter-group">
            <select name="priority" class="form-control">
                <option value="">-- Tất cả mức ưu tiên --</option>
                <option value="low"    <?= $filterPriority === 'low'    ? 'selected' : '' ?>>Thấp</option>
                <option value="medium" <?= $filterPriority === 'medium' ? 'selected' : '' ?>>Trung bình</option>
                <option value="high"   <?= $filterPriority === 'high'   ? 'selected' : '' ?>>Cao</option>
                <option value="urgent" <?= $filterPriority === 'urgent' ? 'selected' : '' ?>>Khẩn cấp</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="/testfinal/public/admin/maintenance" class="btn btn-outline">Đặt lại</a>
    </form>
</div>

<!-- Maintenance Requests Table -->
<div class="card">
    <div class="card-body" style="padding:0">
        <?php if (!empty($requests)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Phòng</th>
                            <th>Tiêu đề</th>
                            <th style="text-align:center">Mức ưu tiên</th>
                            <th style="text-align:center">Trạng thái</th>
                            <th>Báo cáo bởi</th>
                            <th>Ngày báo cáo</th>
                            <th style="text-align:center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $page    = $pagination['current_page'] ?? 1;
                        $perPage = $pagination['per_page'] ?? 20;
                        $offset  = ($page - 1) * $perPage;

                        foreach ($requests as $i => $req):
                            $priorityMap = [
                                'low'    => ['label' => 'Thấp',      'class' => 'badge-neutral'],
                                'medium' => ['label' => 'Trung bình', 'class' => 'badge-info'],
                                'high'   => ['label' => 'Cao',        'class' => 'badge-warning'],
                                'urgent' => ['label' => 'Khẩn cấp',  'class' => 'badge-danger'],
                            ];
                            $pri = $priorityMap[$req['priority'] ?? ''] ?? ['label' => $req['priority'] ?? '—', 'class' => 'badge-neutral'];

                            $statusMap = [
                                'open'        => ['label' => 'Mới mở',       'class' => 'badge-danger'],
                                'in_progress' => ['label' => 'Đang xử lý',   'class' => 'badge-warning'],
                                'resolved'    => ['label' => 'Đã giải quyết','class' => 'badge-success'],
                                'closed'      => ['label' => 'Đã đóng',      'class' => 'badge-neutral'],
                                'rejected'    => ['label' => 'Đã từ chối',   'class' => 'badge-neutral'],
                            ];
                            $st = $statusMap[$req['status'] ?? ''] ?? ['label' => $req['status'] ?? '—', 'class' => 'badge-neutral'];

                            $canResolve = in_array($req['status'] ?? '', ['open', 'in_progress']);
                            $canClose   = in_array($req['status'] ?? '', ['open', 'in_progress', 'resolved']);
                        ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td>
                                <span style="font-weight:600"><?= htmlspecialchars($req['room_number'] ?? '—') ?></span>
                                <div style="font-size:0.78rem;color:var(--text-muted)"><?= htmlspecialchars($req['building_name'] ?? '') ?></div>
                            </td>
                            <td>
                                <div style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
                                     title="<?= htmlspecialchars($req['title'] ?? '') ?>">
                                    <?= htmlspecialchars($req['title'] ?? '—') ?>
                                </div>
                            </td>
                            <td style="text-align:center">
                                <span class="badge <?= $pri['class'] ?>"><?= $pri['label'] ?></span>
                            </td>
                            <td style="text-align:center">
                                <span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px">
                                    <span style="font-size:1.1rem">👤</span>
                                    <?= htmlspecialchars($req['reporter_username'] ?? '—') ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $dt = $req['reported_at'] ?? '';
                                if ($dt) {
                                    echo htmlspecialchars(date('d/m/Y', strtotime($dt)));
                                    echo '<div style="font-size:0.75rem;color:var(--text-muted)">';
                                    echo htmlspecialchars(date('H:i', strtotime($dt)));
                                    echo '</div>';
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td style="text-align:center">
                                <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                                    <a href="/testfinal/public/admin/maintenance/<?= (int)$req['id'] ?>"
                                       class="btn btn-ghost btn-sm">👁 Xem</a>

                                    <?php if ($canResolve): ?>
                                        <form method="POST"
                                              action="/testfinal/public/admin/maintenance/<?= (int)$req['id'] ?>/resolve"
                                              onsubmit="return confirm('Đánh dấu yêu cầu #<?= (int)$req['id'] ?> là đã giải quyết?')"
                                              style="display:inline">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">✅ Giải quyết</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($canClose && ($req['status'] ?? '') !== 'closed'): ?>
                                        <form method="POST"
                                              action="/testfinal/public/admin/maintenance/<?= (int)$req['id'] ?>/close"
                                              onsubmit="return confirm('Đóng yêu cầu bảo trì #<?= (int)$req['id'] ?>?')"
                                              style="display:inline">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
                                            <button type="submit" class="btn btn-outline btn-sm">🔒 Đóng</button>
                                        </form>
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
                <div class="empty-state-icon">🔧</div>
                <div class="empty-state-title">Không có yêu cầu bảo trì nào</div>
                <div class="empty-state-desc">Hiện tại không có yêu cầu bảo trì phù hợp với bộ lọc hiện tại.</div>
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
            'priority' => $filterPriority,
        ]));
        ?>
        <?php if ($cur > 1): ?>
            <a href="/testfinal/public/admin/maintenance?page=<?= $cur - 1 ?>&<?= $qs ?>" class="page-link">‹ Trước</a>
        <?php endif; ?>
        <?php for ($p = max(1, $cur - 2); $p <= min($total, $cur + 2); $p++): ?>
            <a href="/testfinal/public/admin/maintenance?page=<?= $p ?>&<?= $qs ?>"
               class="page-link <?= $p === $cur ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($cur < $total): ?>
            <a href="/testfinal/public/admin/maintenance?page=<?= $cur + 1 ?>&<?= $qs ?>" class="page-link">Sau ›</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
