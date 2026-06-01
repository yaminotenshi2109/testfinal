<?php
/**
 * Admin – Danh sách hóa đơn
 * Variables: $title, $invoices (array), $pagination
 */

// Compute summary stats
$totalCount    = count($invoices ?? []);
$unpaidCount   = 0;
$totalCollected = 0;
foreach ($invoices ?? [] as $inv) {
    if (($inv['status'] ?? '') === 'unpaid' || ($inv['status'] ?? '') === 'overdue') {
        $unpaidCount++;
    }
    if (($inv['status'] ?? '') === 'paid') {
        $totalCollected += (float)($inv['total_amount'] ?? 0);
    }
}

// Filters from GET
$filterMonth  = $_GET['month']  ?? '';
$filterYear   = $_GET['year']   ?? '';
$filterStatus = $_GET['status'] ?? '';

$currentYear = (int)date('Y');
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">🧾 <?= htmlspecialchars($title ?? 'Quản lý hóa đơn') ?></h1>
        <p class="page-subtitle">Quản lý thu tiền phòng và các khoản phí dịch vụ</p>
    </div>
    <div class="page-actions">
        <a href="/testfinal/public/admin/invoices/generate" class="btn btn-primary">
            ➕ Tạo hóa đơn
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="stat-grid" style="margin-bottom:1.5rem">
    <div class="stat-card" style="--stat-color:#6366f1;--stat-icon-bg:#eef2ff">
        <div class="stat-icon">📄</div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalCount ?></div>
            <div class="stat-label">Tổng hóa đơn</div>
        </div>
    </div>
    <div class="stat-card" style="--stat-color:#f59e0b;--stat-icon-bg:#fffbeb">
        <div class="stat-icon">⏳</div>
        <div class="stat-info">
            <div class="stat-value"><?= $unpaidCount ?></div>
            <div class="stat-label">Chưa thanh toán</div>
        </div>
    </div>
    <div class="stat-card" style="--stat-color:#10b981;--stat-icon-bg:#ecfdf5">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalCollected, 0, ',', '.') ?>₫</div>
            <div class="stat-label">Đã thu (trang này)</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="/testfinal/public/admin/invoices" class="filter-bar-form">
        <div class="filter-group">
            <select name="month" class="form-control">
                <option value="">-- Tháng --</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= (string)$filterMonth === (string)$m ? 'selected' : '' ?>>
                        Tháng <?= $m ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <select name="year" class="form-control">
                <option value="">-- Năm --</option>
                <?php for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= (string)$filterYear === (string)$y ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="unpaid"    <?= $filterStatus === 'unpaid'    ? 'selected' : '' ?>>Chưa thanh toán</option>
                <option value="paid"      <?= $filterStatus === 'paid'      ? 'selected' : '' ?>>Đã thanh toán</option>
                <option value="overdue"   <?= $filterStatus === 'overdue'   ? 'selected' : '' ?>>Quá hạn</option>
                <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="/testfinal/public/admin/invoices" class="btn btn-outline">Đặt lại</a>
    </form>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="card-body" style="padding:0">
        <?php if (!empty($invoices)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Sinh viên</th>
                            <th>Phòng</th>
                            <th style="text-align:center">Tháng/Năm</th>
                            <th style="text-align:right">Tiền phòng</th>
                            <th style="text-align:right">Điện + Nước</th>
                            <th style="text-align:right">Tổng</th>
                            <th>Hạn nộp</th>
                            <th style="text-align:center">Trạng thái</th>
                            <th style="text-align:center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $page    = $pagination['current_page'] ?? 1;
                        $perPage = $pagination['per_page'] ?? 20;
                        $offset  = ($page - 1) * $perPage;

                        foreach ($invoices as $i => $inv):
                            $statusMap = [
                                'unpaid'    => ['label' => 'Chưa thanh toán', 'class' => 'badge-warning'],
                                'paid'      => ['label' => 'Đã thanh toán',   'class' => 'badge-success'],
                                'overdue'   => ['label' => 'Quá hạn',         'class' => 'badge-danger'],
                                'cancelled' => ['label' => 'Đã hủy',          'class' => 'badge-neutral'],
                            ];
                            $st = $statusMap[$inv['status'] ?? ''] ?? ['label' => $inv['status'] ?? '—', 'class' => 'badge-neutral'];

                            $elecWater = (float)($inv['electricity_fee'] ?? 0) + (float)($inv['water_fee'] ?? 0);
                            $dueDate   = $inv['due_date'] ?? '';
                            $isPastDue = $dueDate && strtotime($dueDate) < time() && ($inv['status'] ?? '') === 'unpaid';
                        ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($inv['full_name'] ?? '—') ?></div>
                                <?php if (!empty($inv['paid_at'])): ?>
                                    <div style="font-size:0.75rem;color:var(--text-muted)">
                                        Thanh toán: <?= htmlspecialchars(date('d/m/Y', strtotime($inv['paid_at']))) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight:600"><?= htmlspecialchars($inv['room_number'] ?? '—') ?></span>
                                <div style="font-size:0.78rem;color:var(--text-muted)"><?= htmlspecialchars($inv['building_name'] ?? '') ?></div>
                            </td>
                            <td style="text-align:center">
                                <?= sprintf('%02d/%d', (int)($inv['month'] ?? 0), (int)($inv['year'] ?? 0)) ?>
                            </td>
                            <td style="text-align:right">
                                <?= number_format((float)($inv['base_rent'] ?? 0), 0, ',', '.') ?>₫
                            </td>
                            <td style="text-align:right">
                                <?= number_format($elecWater, 0, ',', '.') ?>₫
                            </td>
                            <td style="text-align:right;font-weight:700;color:var(--primary)">
                                <?= number_format((float)($inv['total_amount'] ?? 0), 0, ',', '.') ?>₫
                            </td>
                            <td>
                                <?php if ($dueDate): ?>
                                    <span style="<?= $isPastDue ? 'color:#ef4444;font-weight:600' : '' ?>">
                                        <?= htmlspecialchars(date('d/m/Y', strtotime($dueDate))) ?>
                                    </span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center">
                                <span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
                            </td>
                            <td style="text-align:center">
                                <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                                    <a href="/testfinal/public/admin/invoices/<?= (int)$inv['id'] ?>"
                                       class="btn btn-ghost btn-sm">👁 Xem</a>
                                    <?php if (($inv['status'] ?? '') === 'unpaid' || ($inv['status'] ?? '') === 'overdue'): ?>
                                        <form method="POST"
                                              action="/testfinal/public/admin/invoices/<?= (int)$inv['id'] ?>/mark-paid"
                                              onsubmit="return confirm('Xác nhận đánh dấu hóa đơn #<?= (int)$inv['id'] ?> là đã thanh toán?')"
                                              style="display:inline">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">✅ Đã thu</button>
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
                <div class="empty-state-icon">🧾</div>
                <div class="empty-state-title">Không có hóa đơn nào</div>
                <div class="empty-state-desc">Không tìm thấy hóa đơn phù hợp với bộ lọc hiện tại.</div>
                <a href="/testfinal/public/admin/invoices/generate" class="btn btn-primary" style="margin-top:1rem">
                    ➕ Tạo hóa đơn mới
                </a>
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
            'month'  => $filterMonth,
            'year'   => $filterYear,
            'status' => $filterStatus,
        ]));
        ?>
        <?php if ($cur > 1): ?>
            <a href="/testfinal/public/admin/invoices?page=<?= $cur - 1 ?>&<?= $qs ?>" class="page-link">‹ Trước</a>
        <?php endif; ?>
        <?php for ($p = max(1, $cur - 2); $p <= min($total, $cur + 2); $p++): ?>
            <a href="/testfinal/public/admin/invoices?page=<?= $p ?>&<?= $qs ?>"
               class="page-link <?= $p === $cur ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($cur < $total): ?>
            <a href="/testfinal/public/admin/invoices?page=<?= $cur + 1 ?>&<?= $qs ?>" class="page-link">Sau ›</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
