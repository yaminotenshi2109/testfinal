<?php
// Views: student/dashboard.php
// Variables: $title, $student, $contract, $unpaid_invoices, $active_violations, $recent_notifications
$studentName   = $student['full_name']    ?? 'Sinh viên';
$studentCode   = $student['student_code'] ?? '';
$faculty       = $student['faculty']      ?? '';
$priorityLevel = $student['priority_level'] ?? '';

// Generate initials from name
$nameParts = explode(' ', trim($studentName));
$initials  = '';
if (count($nameParts) >= 2) {
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr(end($nameParts), 0, 1));
} else {
    $initials = mb_strtoupper(mb_substr($studentName, 0, 2));
}

$unpaidInvoices   = (int)($unpaid_invoices ?? 0);
$activeViolations = (int)($active_violations ?? 0);
$recentNotifs     = $recent_notifications ?? [];

$hasContract = !empty($contract);
?>

<!-- Welcome Banner -->
<div class="card mb-24" style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;color:#fff;padding:28px 32px;">
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
        <div class="avatar avatar-xl" style="flex-shrink:0;width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;letter-spacing:1px;border:2px solid rgba(255,255,255,0.4);">
            <?= htmlspecialchars($initials) ?>
        </div>
        <div style="flex:1;min-width:200px;">
            <h2 style="font-size:22px;font-weight:800;margin:0 0 6px 0;line-height:1.2;">
                Xin chào, <?= htmlspecialchars($studentName) ?>! 👋
            </h2>
            <p style="opacity:.8;margin:0;font-size:14px;">
                Mã SV: <strong><?= htmlspecialchars($studentCode) ?></strong>
                <?php if ($faculty): ?> • <?= htmlspecialchars($faculty) ?><?php endif; ?>
                <?php if ($priorityLevel): ?> • Ưu tiên: <strong><?= htmlspecialchars($priorityLevel) ?></strong><?php endif; ?>
            </p>
        </div>
        <div style="text-align:right;flex-shrink:0;">
            <a href="<?= getDynamicUrl('/student/profile') ?>" class="btn" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.4);font-size:13px;">
                👤 Hồ sơ của tôi
            </a>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="stat-grid mb-24">
    <!-- Hoá đơn chưa thanh toán -->
    <div class="stat-card" style="--stat-color:<?= $unpaidInvoices > 0 ? '#ef4444' : '#22c55e' ?>;--stat-icon-bg:<?= $unpaidInvoices > 0 ? '#fef2f2' : '#f0fdf4' ?>;">
        <div class="stat-icon"><?= $unpaidInvoices > 0 ? '💳' : '✅' ?></div>
        <div class="stat-body">
            <div class="stat-value"><?= $unpaidInvoices ?></div>
            <div class="stat-label">Hóa đơn chưa thanh toán</div>
        </div>
        <a href="<?= getDynamicUrl('/student/invoices') ?>" class="stat-link" style="font-size:12px;color:var(--stat-color);text-decoration:none;display:block;margin-top:8px;">
            Xem hóa đơn →
        </a>
    </div>

    <!-- Vi phạm đang hiệu lực -->
    <div class="stat-card" style="--stat-color:<?= $activeViolations > 0 ? '#f59e0b' : '#22c55e' ?>;--stat-icon-bg:<?= $activeViolations > 0 ? '#fffbeb' : '#f0fdf4' ?>;">
        <div class="stat-icon"><?= $activeViolations > 0 ? '⚠️' : '🛡️' ?></div>
        <div class="stat-body">
            <div class="stat-value"><?= $activeViolations ?></div>
            <div class="stat-label">Vi phạm đang hiệu lực</div>
        </div>
        <a href="<?= getDynamicUrl('/student/violations') ?>" class="stat-link" style="font-size:12px;color:var(--stat-color);text-decoration:none;display:block;margin-top:8px;">
            Xem vi phạm →
        </a>
    </div>

    <!-- Trạng thái phòng -->
    <div class="stat-card" style="--stat-color:<?= $hasContract ? '#4f46e5' : '#6b7280' ?>;--stat-icon-bg:<?= $hasContract ? '#eef2ff' : '#f9fafb' ?>;">
        <div class="stat-icon"><?= $hasContract ? '🏠' : '🔍' ?></div>
        <div class="stat-body">
            <div class="stat-value" style="font-size:18px;">
                <?= $hasContract ? htmlspecialchars($contract['room_number'] ?? '--') : 'Chưa có' ?>
            </div>
            <div class="stat-label">
                <?php if ($hasContract): ?>
                    <?= htmlspecialchars($contract['building_name'] ?? '') ?>
                <?php else: ?>
                    Chưa đăng ký phòng
                <?php endif; ?>
            </div>
        </div>
        <?php if ($hasContract): ?>
            <span class="stat-link" style="font-size:12px;color:var(--stat-color);display:block;margin-top:8px;">
                <?php
                $cStatus = $contract['status'] ?? '';
                $statusLabel = match($cStatus) {
                    'active'    => '🟢 Đang ở',
                    'expired'   => '🔴 Đã hết hạn',
                    'cancelled' => '⚫ Đã huỷ',
                    default     => $cStatus
                };
                echo htmlspecialchars($statusLabel);
                ?>
            </span>
        <?php else: ?>
            <a href="<?= getDynamicUrl('/student/registrations/create') ?>" class="stat-link" style="font-size:12px;color:var(--stat-color);text-decoration:none;display:block;margin-top:8px;">
                Đăng ký ngay →
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Main Content: 2 columns -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;" class="grid-2-dashboard">

    <!-- Left: Room / Contract Info -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🏠 Thông tin phòng ở</h3>
        </div>
        <div class="card-body">
            <?php if ($hasContract): ?>
                <?php
                $cStatus = $contract['status'] ?? '';
                $statusBadge = match($cStatus) {
                    'active'    => '<span class="badge badge-success">Đang ở</span>',
                    'expired'   => '<span class="badge badge-danger">Hết hạn</span>',
                    'cancelled' => '<span class="badge badge-neutral">Đã huỷ</span>',
                    default     => '<span class="badge badge-info">' . htmlspecialchars($cStatus) . '</span>',
                };
                ?>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:28px;font-weight:800;color:#4f46e5;">
                            Phòng <?= htmlspecialchars($contract['room_number'] ?? '--') ?>
                        </span>
                        <?= $statusBadge ?>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div style="background:#f8fafc;border-radius:10px;padding:12px;">
                            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Tòa nhà</div>
                            <div style="font-weight:700;color:#1e293b;"><?= htmlspecialchars($contract['building_name'] ?? '--') ?></div>
                        </div>
                        <div style="background:#f8fafc;border-radius:10px;padding:12px;">
                            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Tiền phòng/tháng</div>
                            <div style="font-weight:700;color:#1e293b;">
                                <?= number_format((float)($contract['monthly_fee'] ?? 0), 0, ',', '.') ?> ₫
                            </div>
                        </div>
                        <div style="background:#f8fafc;border-radius:10px;padding:12px;">
                            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Ngày bắt đầu</div>
                            <div style="font-weight:700;color:#1e293b;">
                                <?= htmlspecialchars($contract['start_date'] ?? '--') ?>
                            </div>
                        </div>
                        <div style="background:#f8fafc;border-radius:10px;padding:12px;">
                            <div style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Ngày kết thúc</div>
                            <div style="font-weight:700;color:#1e293b;">
                                <?= htmlspecialchars($contract['end_date'] ?? '--') ?>
                            </div>
                        </div>
                    </div>

                    <div style="border-top:1px solid #e5e7eb;padding-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
                        <a href="<?= getDynamicUrl('/student/invoices') ?>" class="btn btn-primary btn-sm">
                            💳 Xem hóa đơn
                        </a>
                        <a href="<?= getDynamicUrl('/student/registrations') ?>" class="btn btn-outline btn-sm">
                            📋 Đăng ký của tôi
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding:40px 20px;">
                    <div style="font-size:52px;margin-bottom:12px;">🏠</div>
                    <h4 style="font-weight:700;color:#374151;margin-bottom:8px;">Chưa có phòng ở</h4>
                    <p style="color:#6b7280;font-size:14px;margin-bottom:20px;max-width:260px;margin-left:auto;margin-right:auto;">
                        Bạn chưa đăng ký phòng ký túc xá hoặc đơn chưa được phê duyệt.
                    </p>
                    <a href="<?= getDynamicUrl('/student/registrations/create') ?>" class="btn btn-primary">
                        ➕ Đăng ký phòng ngay
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Recent Notifications -->
    <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h3 class="card-title">🔔 Thông báo gần đây</h3>
            <a href="<?= getDynamicUrl('/student/notifications') ?>" class="btn btn-ghost btn-sm">Xem tất cả</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($recentNotifs)): ?>
                <div class="empty-state" style="padding:40px 20px;">
                    <div style="font-size:40px;margin-bottom:10px;">📭</div>
                    <p style="color:#6b7280;font-size:14px;margin:0;">Chưa có thông báo nào.</p>
                </div>
            <?php else: ?>
                <div class="notif-list" style="max-height:380px;overflow-y:auto;">
                    <?php foreach ($recentNotifs as $notif): ?>
                        <?php
                        $isRead  = !empty($notif['is_read']);
                        $nType   = $notif['type'] ?? 'general';
                        $typeIcon = match($nType) {
                            'invoice'   => '💳',
                            'violation' => '⚠️',
                            'contract'  => '📄',
                            'system'    => '⚙️',
                            default     => '🔔',
                        };
                        ?>
                        <div class="notif-item <?= $isRead ? '' : 'notif-unread' ?>"
                             style="<?= $isRead ? '' : 'background:#eff6ff;' ?>padding:14px 16px;border-bottom:1px solid #f1f5f9;display:flex;gap:12px;align-items:flex-start;cursor:pointer;"
                             onclick="window.location='<?= getDynamicUrl('/student/notifications') ?>'">
                            <span style="font-size:20px;flex-shrink:0;margin-top:1px;"><?= $typeIcon ?></span>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:<?= $isRead ? '500' : '700' ?>;color:#1e293b;font-size:13.5px;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($notif['title'] ?? '') ?>
                                    <?php if (!$isRead): ?>
                                        <span style="display:inline-block;width:7px;height:7px;background:#3b82f6;border-radius:50%;margin-left:5px;vertical-align:middle;"></span>
                                    <?php endif; ?>
                                </div>
                                <div style="color:#6b7280;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars(mb_strimwidth($notif['message'] ?? '', 0, 80, '...')) ?>
                                </div>
                                <div style="color:#94a3b8;font-size:11px;margin-top:4px;">
                                    🕐 <?= htmlspecialchars($notif['sent_at'] ?? '') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($recentNotifs)): ?>
            <div class="card-footer" style="text-align:center;">
                <a href="<?= getDynamicUrl('/student/notifications') ?>" class="btn btn-outline btn-sm" style="width:100%;">
                    Xem tất cả thông báo
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions (full-width bottom) -->
<div class="card mt-24">
    <div class="card-header">
        <h3 class="card-title">⚡ Thao tác nhanh</h3>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">
            <a href="<?= getDynamicUrl('/student/invoices') ?>" class="btn btn-outline" style="flex-direction:column;gap:6px;padding:18px 12px;height:auto;text-align:center;">
                <span style="font-size:26px;">💳</span>
                <span style="font-size:13px;">Hóa đơn</span>
            </a>
            <a href="<?= getDynamicUrl('/student/registrations') ?>" class="btn btn-outline" style="flex-direction:column;gap:6px;padding:18px 12px;height:auto;text-align:center;">
                <span style="font-size:26px;">📋</span>
                <span style="font-size:13px;">Đăng ký phòng</span>
            </a>
            <a href="<?= getDynamicUrl('/student/violations') ?>" class="btn btn-outline" style="flex-direction:column;gap:6px;padding:18px 12px;height:auto;text-align:center;">
                <span style="font-size:26px;">📝</span>
                <span style="font-size:13px;">Vi phạm</span>
            </a>
            <a href="<?= getDynamicUrl('/student/notifications') ?>" class="btn btn-outline" style="flex-direction:column;gap:6px;padding:18px 12px;height:auto;text-align:center;">
                <span style="font-size:26px;">🔔</span>
                <span style="font-size:13px;">Thông báo</span>
            </a>
            <a href="<?= getDynamicUrl('/student/profile') ?>" class="btn btn-outline" style="flex-direction:column;gap:6px;padding:18px 12px;height:auto;text-align:center;">
                <span style="font-size:26px;">👤</span>
                <span style="font-size:13px;">Hồ sơ</span>
            </a>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .grid-2-dashboard {
        grid-template-columns: 1fr !important;
    }
}
</style>
