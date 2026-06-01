<?php
/**
 * app/views/admin/dashboard.php
 * ─────────────────────────────────────────────────────────────
 *  Admin dashboard — tổng quan hệ thống KTX
 *  Variables: $title, $stats[], $recent_registrations[], $recent_violations[]
 * ─────────────────────────────────────────────────────────────
 */

// Helpers for occupancy ratio
$totalRooms    = $stats['total_rooms']    ?? 0;
$occupiedRooms = $stats['occupied_rooms'] ?? 0;
$occupancyPct  = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;
?>

<!-- ── Page Header ──────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">🏠 Dashboard</h1>
        <p class="page-subtitle">Tổng quan hệ thống Ký túc xá</p>
    </div>
    <div class="page-actions">
        <span style="font-size:12px;color:var(--txt-muted);background:var(--card-bg);border:1px solid var(--border);padding:6px 12px;border-radius:var(--radius-sm);">
            📅 <?= date('d/m/Y H:i') ?>
        </span>
        <a href="/testfinal/public/admin/reports" class="btn btn-outline btn-sm">📊 Báo cáo</a>
        <a href="/testfinal/public/admin/registrations" class="btn btn-primary btn-sm">➕ Đăng ký mới</a>
    </div>
</div>

<!-- ── Flash messages ───────────────────────────────────────── -->
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success mb-16">
        <span class="alert-icon">✅</span>
        <div class="alert-content">
            <div class="alert-msg"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        </div>
        <button class="alert-close" onclick="this.closest('.alert').remove()">×</button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<!-- ── Stat Grid ────────────────────────────────────────────── -->
<div class="stat-grid mb-24">

    <!-- Tổng số phòng -->
    <div class="stat-card" style="--stat-color:#6366f1;--stat-icon-bg:#eef2ff">
        <div class="stat-icon">🚪</div>
        <div>
            <div class="stat-value" data-count="<?= $stats['total_rooms'] ?? 0 ?>">
                <?= number_format($stats['total_rooms'] ?? 0) ?>
            </div>
            <div class="stat-label">Tổng số phòng</div>
        </div>
    </div>

    <!-- Phòng đã có người -->
    <div class="stat-card" style="--stat-color:#3b82f6;--stat-icon-bg:#eff6ff">
        <div class="stat-icon">🛏️</div>
        <div>
            <div class="stat-value" data-count="<?= $stats['occupied_rooms'] ?? 0 ?>">
                <?= number_format($stats['occupied_rooms'] ?? 0) ?>
            </div>
            <div class="stat-label">Phòng đã có người</div>
        </div>
        <div style="margin-top:4px">
            <div class="progress" style="width:100%">
                <div class="progress-bar" style="width:<?= $occupancyPct ?>%;background:#3b82f6"></div>
            </div>
            <div style="font-size:11px;color:var(--txt-muted);margin-top:4px"><?= $occupancyPct ?>% lấp đầy</div>
        </div>
    </div>

    <!-- Phòng còn trống -->
    <div class="stat-card" style="--stat-color:#10b981;--stat-icon-bg:#d1fae5">
        <div class="stat-icon">✅</div>
        <div>
            <div class="stat-value" data-count="<?= $stats['available_rooms'] ?? 0 ?>">
                <?= number_format($stats['available_rooms'] ?? 0) ?>
            </div>
            <div class="stat-label">Phòng còn trống</div>
        </div>
    </div>

    <!-- Tổng sinh viên -->
    <div class="stat-card" style="--stat-color:#8b5cf6;--stat-icon-bg:#ede9fe">
        <div class="stat-icon">🎓</div>
        <div>
            <div class="stat-value" data-count="<?= $stats['total_students'] ?? 0 ?>">
                <?= number_format($stats['total_students'] ?? 0) ?>
            </div>
            <div class="stat-label">Sinh viên</div>
        </div>
    </div>

    <!-- Hợp đồng -->
    <div class="stat-card" style="--stat-color:#06b6d4;--stat-icon-bg:#cffafe">
        <div class="stat-icon">📄</div>
        <div>
            <div class="stat-value" data-count="<?= $stats['total_contracts'] ?? 0 ?>">
                <?= number_format($stats['total_contracts'] ?? 0) ?>
            </div>
            <div class="stat-label">Hợp đồng active</div>
        </div>
    </div>

    <!-- Hóa đơn chưa thanh toán -->
    <div class="stat-card" style="--stat-color:#f59e0b;--stat-icon-bg:#fef3c7">
        <div class="stat-icon">💰</div>
        <div>
            <div class="stat-value" data-count="<?= $stats['unpaid_invoices'] ?? 0 ?>">
                <?= number_format($stats['unpaid_invoices'] ?? 0) ?>
            </div>
            <div class="stat-label">Hóa đơn chưa trả</div>
        </div>
    </div>

    <!-- Đơn đăng ký chờ duyệt -->
    <div class="stat-card" style="--stat-color:#ec4899;--stat-icon-bg:#fce7f3">
        <div class="stat-icon">📋</div>
        <div>
            <div class="stat-value" data-count="<?= $stats['pending_registrations'] ?? 0 ?>">
                <?= number_format($stats['pending_registrations'] ?? 0) ?>
            </div>
            <div class="stat-label">Đơn chờ duyệt</div>
        </div>
        <?php if (($stats['pending_registrations'] ?? 0) > 0): ?>
            <a href="/testfinal/public/admin/registrations?status=pending"
               style="font-size:11px;color:#ec4899;font-weight:600;text-decoration:underline;margin-top:4px">
                Xem ngay →
            </a>
        <?php endif; ?>
    </div>

    <!-- Vi phạm đang mở -->
    <div class="stat-card" style="--stat-color:#ef4444;--stat-icon-bg:#fee2e2">
        <div class="stat-icon">⚠️</div>
        <div>
            <div class="stat-value" data-count="<?= $stats['open_violations'] ?? 0 ?>">
                <?= number_format($stats['open_violations'] ?? 0) ?>
            </div>
            <div class="stat-label">Vi phạm chưa xử lý</div>
        </div>
        <?php if (($stats['open_violations'] ?? 0) > 0): ?>
            <a href="/testfinal/public/admin/violations?status=open"
               style="font-size:11px;color:#ef4444;font-weight:600;text-decoration:underline;margin-top:4px">
                Xem ngay →
            </a>
        <?php endif; ?>
    </div>

</div><!-- /.stat-grid -->

<!-- ── Occupancy Summary Bar ────────────────────────────────── -->
<div class="card mb-24">
    <div class="card-body" style="padding:16px 20px">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <div style="font-size:13px;font-weight:600;color:var(--txt-secondary);min-width:140px">
                📊 Tỷ lệ lấp đầy tổng thể
            </div>
            <div style="flex:1;min-width:200px">
                <div class="progress">
                    <div class="progress-bar <?= $occupancyPct >= 90 ? 'danger' : ($occupancyPct >= 70 ? 'warning' : 'success') ?>"
                         style="width:<?= $occupancyPct ?>%"></div>
                </div>
            </div>
            <div style="font-size:18px;font-weight:800;color:var(--txt-primary);min-width:55px;text-align:right">
                <?= $occupancyPct ?>%
            </div>
            <div style="font-size:12px;color:var(--txt-muted)">
                <?= number_format($occupiedRooms) ?> / <?= number_format($totalRooms) ?> phòng
            </div>
        </div>
    </div>
</div>

<!-- ── Two-column: Recent Registrations + Recent Violations ─── -->
<div class="grid-2">

    <!-- Đơn đăng ký gần đây -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">📋 Đơn đăng ký gần đây</div>
                <div class="card-subtitle">5 đơn mới nhất trong hệ thống</div>
            </div>
            <a href="/testfinal/public/admin/registrations" class="btn btn-ghost btn-sm">Xem tất cả →</a>
        </div>

        <?php if (!empty($recent_registrations)): ?>
            <div class="table-wrapper" style="border:none;border-radius:0;box-shadow:none">
                <table>
                    <thead>
                        <tr>
                            <th>Sinh viên</th>
                            <th>Phòng yêu cầu</th>
                            <th>Ngày nộp</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_registrations as $reg): ?>
                            <?php
                                $statusMap = [
                                    'pending'  => ['badge-warning', '⏳ Chờ duyệt'],
                                    'approved' => ['badge-success', '✅ Đã duyệt'],
                                    'rejected' => ['badge-danger',  '❌ Từ chối'],
                                ];
                                $status    = $reg['status'] ?? 'pending';
                                [$badgeClass, $statusLabel] = $statusMap[$status] ?? ['badge-neutral', $status];
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div class="avatar avatar-sm">
                                            <?= mb_strtoupper(mb_substr($reg['student_name'] ?? 'S', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:13px">
                                                <?= htmlspecialchars($reg['student_name'] ?? 'N/A') ?>
                                            </div>
                                            <div class="sub"><?= htmlspecialchars($reg['student_code'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight:600"><?= htmlspecialchars($reg['room_number'] ?? '—') ?></span>
                                    <?php if (!empty($reg['building_name'])): ?>
                                        <div class="sub"><?= htmlspecialchars($reg['building_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size:12px;color:var(--txt-muted)">
                                        <?= !empty($reg['created_at']) ? date('d/m/Y', strtotime($reg['created_at'])) : '—' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:40px 24px">
                <div class="empty-icon">📋</div>
                <div class="empty-title">Chưa có đơn đăng ký</div>
                <div class="empty-msg">Các đơn đăng ký mới sẽ hiển thị tại đây.</div>
            </div>
        <?php endif; ?>

        <?php if (!empty($recent_registrations) && ($stats['pending_registrations'] ?? 0) > 0): ?>
            <div class="card-footer" style="text-align:center">
                <a href="/testfinal/public/admin/registrations?status=pending"
                   class="btn btn-outline btn-sm">
                    ⏳ Xem <?= $stats['pending_registrations'] ?> đơn chờ duyệt
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Vi phạm gần đây -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">⚠️ Vi phạm gần đây</div>
                <div class="card-subtitle">Các trường hợp vi phạm mới nhất</div>
            </div>
            <a href="/testfinal/public/admin/violations" class="btn btn-ghost btn-sm">Xem tất cả →</a>
        </div>

        <?php if (!empty($recent_violations)): ?>
            <div class="notif-list" style="padding:8px 0">
                <?php foreach ($recent_violations as $v): ?>
                    <?php
                        $vStatus = $v['status'] ?? 'open';
                        $isOpen  = $vStatus === 'open';
                    ?>
                    <div class="notif-item <?= $isOpen ? 'unread' : '' ?>">
                        <div class="notif-dot"></div>
                        <div class="notif-body">
                            <div class="notif-title">
                                <?= htmlspecialchars($v['student_name'] ?? 'Sinh viên') ?>
                                <span style="font-weight:400;color:var(--txt-muted)">—</span>
                                <?= htmlspecialchars($v['violation_type'] ?? 'Vi phạm nội quy') ?>
                            </div>
                            <div class="notif-msg">
                                🚪 Phòng <?= htmlspecialchars($v['room_number'] ?? '—') ?>
                                <?php if (!empty($v['description'])): ?>
                                    · <?= htmlspecialchars(mb_strimwidth($v['description'], 0, 60, '...')) ?>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;margin-top:5px">
                                <?php
                                    $vBadge = match($vStatus) {
                                        'open'     => ['badge-danger',  '🔴 Chưa xử lý'],
                                        'resolved' => ['badge-success', '✅ Đã xử lý'],
                                        'pending'  => ['badge-warning', '⏳ Đang xem xét'],
                                        default    => ['badge-neutral', $vStatus],
                                    };
                                ?>
                                <span class="badge <?= $vBadge[0] ?>"><?= $vBadge[1] ?></span>
                                <?php if (!empty($v['fine_amount']) && $v['fine_amount'] > 0): ?>
                                    <span style="font-size:11px;color:var(--danger);font-weight:600">
                                        💸 <?= number_format($v['fine_amount']) ?>đ
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="notif-time">
                            <?= !empty($v['created_at']) ? date('d/m', strtotime($v['created_at'])) : '' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:40px 24px">
                <div class="empty-icon">🎉</div>
                <div class="empty-title">Không có vi phạm</div>
                <div class="empty-msg">Tuyệt vời! Hiện không có vi phạm nào cần xử lý.</div>
            </div>
        <?php endif; ?>

        <?php if (!empty($recent_violations) && ($stats['open_violations'] ?? 0) > 0): ?>
            <div class="card-footer" style="text-align:center">
                <a href="/testfinal/public/admin/violations?status=open"
                   class="btn btn-danger btn-sm">
                    ⚠️ Xử lý <?= $stats['open_violations'] ?> vi phạm
                </a>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /.grid-2 -->

<!-- ── Quick Links ───────────────────────────────────────────── -->
<div class="card mt-24">
    <div class="card-header">
        <div class="card-title">⚡ Truy cập nhanh</div>
    </div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:10px">
            <a href="/testfinal/public/admin/rooms" class="btn btn-outline">🚪 Quản lý phòng</a>
            <a href="/testfinal/public/admin/students" class="btn btn-outline">🎓 Quản lý sinh viên</a>
            <a href="/testfinal/public/admin/contracts" class="btn btn-outline">📄 Hợp đồng</a>
            <a href="/testfinal/public/admin/invoices" class="btn btn-outline">💰 Hóa đơn</a>
            <a href="/testfinal/public/admin/violations" class="btn btn-outline">⚠️ Vi phạm</a>
            <a href="/testfinal/public/admin/users" class="btn btn-outline">👤 Tài khoản</a>
            <a href="/testfinal/public/admin/services" class="btn btn-outline">🔧 Dịch vụ</a>
            <a href="/testfinal/public/admin/reports" class="btn btn-primary">📊 Báo cáo tổng hợp</a>
        </div>
    </div>
</div>

<script>
// Animate stat counters on load
document.addEventListener('DOMContentLoaded', function () {
    const counters = document.querySelectorAll('.stat-value[data-count]');
    counters.forEach(function (el) {
        const target = parseInt(el.getAttribute('data-count'), 10);
        if (isNaN(target) || target === 0) return;
        let start = 0;
        const duration = 900;
        const step = Math.ceil(target / (duration / 16));
        const timer = setInterval(function () {
            start += step;
            if (start >= target) {
                el.textContent = target.toLocaleString('vi-VN');
                clearInterval(timer);
            } else {
                el.textContent = start.toLocaleString('vi-VN');
            }
        }, 16);
    });
});
</script>
