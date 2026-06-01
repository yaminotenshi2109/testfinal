<?php
// Views: student/registrations/index.php
// Variables: $title, $registrations

$registrations = $registrations ?? [];

// Determine if user has active (pending/approved) registrations
$hasActiveReg = false;
foreach ($registrations as $reg) {
    $s = strtolower($reg['status'] ?? '');
    if ($s === 'pending' || $s === 'approved') {
        $hasActiveReg = true;
        break;
    }
}

function regStatusBadge(string $status): string {
    return match(strtolower($status)) {
        'pending'  => '<span class="badge badge-warning">⏳ Chờ duyệt</span>',
        'approved' => '<span class="badge badge-success">✅ Đã duyệt</span>',
        'rejected' => '<span class="badge badge-danger">❌ Từ chối</span>',
        'cancelled' => '<span class="badge badge-neutral">🚫 Đã huỷ</span>',
        'assigned' => '<span class="badge badge-info">🏠 Đã phân phòng</span>',
        default    => '<span class="badge badge-neutral">' . htmlspecialchars($status) . '</span>',
    };
}

function semesterLabel(string $sem): string {
    return match($sem) {
        '1' => 'Học kỳ 1',
        '2' => 'Học kỳ 2',
        '3' => 'Học kỳ hè',
        default => 'Học kỳ ' . htmlspecialchars($sem),
    };
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">📋 Đăng ký phòng của tôi</h1>
        <p class="page-subtitle">Quản lý các đơn đăng ký ký túc xá của bạn</p>
    </div>
    <div class="page-actions">
        <?php if (!$hasActiveReg): ?>
            <a href="/testfinal/public/student/registrations/create" class="btn btn-primary">
                ➕ Đăng ký mới
            </a>
        <?php else: ?>
            <button class="btn btn-outline" disabled title="Bạn đã có đơn đang chờ xử lý" style="cursor:not-allowed;opacity:.6;">
                ➕ Đăng ký mới
            </button>
        <?php endif; ?>
        <a href="/testfinal/public/student/dashboard" class="btn btn-ghost">← Về trang chủ</a>
    </div>
</div>

<?php if ($hasActiveReg): ?>
    <div class="alert alert-info mb-20" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 18px;display:flex;gap:12px;align-items:flex-start;">
        <span style="font-size:18px;">ℹ️</span>
        <div style="font-size:14px;color:#1e40af;">
            Bạn đang có đơn đăng ký đang chờ xử lý hoặc đã được duyệt. Vui lòng chờ kết quả trước khi tạo đơn mới.
        </div>
    </div>
<?php endif; ?>

<?php if (empty($registrations)): ?>
    <!-- Empty State -->
    <div class="card">
        <div class="card-body">
            <div class="empty-state" style="padding:60px 20px;text-align:center;">
                <div style="font-size:64px;margin-bottom:16px;">📭</div>
                <h3 style="font-weight:700;color:#374151;font-size:18px;margin-bottom:8px;">Chưa có đơn đăng ký nào</h3>
                <p style="color:#6b7280;font-size:14px;margin-bottom:24px;max-width:320px;margin-left:auto;margin-right:auto;">
                    Bạn chưa có đơn đăng ký phòng ký túc xá nào. Hãy tạo đơn đăng ký để được sắp xếp phòng ở.
                </p>
                <a href="/testfinal/public/student/registrations/create" class="btn btn-primary">
                    ➕ Tạo đơn đăng ký ngay
                </a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Registration Cards -->
    <div style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach ($registrations as $reg): ?>
            <?php
            $regId     = (int)($reg['id'] ?? 0);
            $status    = $reg['status'] ?? '';
            $isPending = strtolower($status) === 'pending';
            $createdAt = $reg['created_at'] ?? '';
            $roomNum   = $reg['room_number'] ?? null;
            $building  = $reg['building_name'] ?? null;
            $floor     = $reg['floor'] ?? null;
            $semester  = $reg['semester'] ?? '';
            $year      = $reg['academic_year'] ?? '';
            $notes     = $reg['notes'] ?? '';
            ?>
            <div class="card" style="border-left:4px solid <?= match(strtolower($status)) {
                'pending'   => '#f59e0b',
                'approved'  => '#22c55e',
                'assigned'  => '#3b82f6',
                'rejected'  => '#ef4444',
                'cancelled' => '#6b7280',
                default     => '#e5e7eb'
            } ?>;">
                <div class="card-body">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                        <!-- Left info -->
                        <div style="flex:1;min-width:220px;">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap;">
                                <span style="font-size:20px;">🗓️</span>
                                <h3 style="font-weight:700;font-size:16px;color:#1e293b;margin:0;">
                                    <?= semesterLabel((string)$semester) ?> — <?= htmlspecialchars($year) ?>
                                </h3>
                                <?= regStatusBadge($status) ?>
                            </div>

                            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:10px;">
                                <div style="background:#f8fafc;border-radius:8px;padding:10px 12px;">
                                    <div style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Tòa ưu tiên</div>
                                    <div style="font-weight:600;color:#374151;font-size:13.5px;">
                                        <?= htmlspecialchars($building ?? 'Không chọn') ?>
                                    </div>
                                </div>

                                <div style="background:#f8fafc;border-radius:8px;padding:10px 12px;">
                                    <div style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Phòng được phân</div>
                                    <div style="font-weight:600;color:#374151;font-size:13.5px;">
                                        <?php if ($roomNum): ?>
                                            🏠 Phòng <?= htmlspecialchars($roomNum) ?>
                                            <?php if ($floor): ?>(Tầng <?= htmlspecialchars($floor) ?>)<?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;">Chưa phân phòng</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div style="background:#f8fafc;border-radius:8px;padding:10px 12px;">
                                    <div style="font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Ngày nộp đơn</div>
                                    <div style="font-weight:600;color:#374151;font-size:13.5px;">
                                        <?= htmlspecialchars($createdAt) ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($notes): ?>
                                <div style="background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;font-size:13px;color:#713f12;">
                                    <strong>Ghi chú:</strong> <?= htmlspecialchars($notes) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Right actions -->
                        <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;flex-shrink:0;">
                            <a href="/testfinal/public/student/registrations/<?= $regId ?>" class="btn btn-outline btn-sm">
                                👁️ Chi tiết
                            </a>
                            <?php if ($isPending): ?>
                                <button class="btn btn-danger btn-sm"
                                        onclick="confirmCancel(<?= $regId ?>)"
                                        data-reg-id="<?= $regId ?>">
                                    🚫 Huỷ đơn
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Cancel Confirmation Modal -->
<div class="modal-overlay" id="cancelModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="modal" style="background:#fff;border-radius:16px;padding:28px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="font-size:48px;margin-bottom:12px;">🚫</div>
            <h3 style="font-weight:700;font-size:18px;color:#1e293b;margin-bottom:8px;">Xác nhận huỷ đơn</h3>
            <p style="color:#6b7280;font-size:14px;">Bạn có chắc chắn muốn huỷ đơn đăng ký này không? Hành động này không thể hoàn tác.</p>
        </div>
        <div style="display:flex;gap:12px;justify-content:center;">
            <button onclick="document.getElementById('cancelModal').style.display='none'" class="btn btn-outline" style="flex:1;">
                Không, giữ lại
            </button>
            <button onclick="doCancel()" class="btn btn-danger" style="flex:1;" id="confirmCancelBtn">
                Huỷ đơn
            </button>
        </div>
    </div>
</div>

<script>
let cancelRegId = null;

function confirmCancel(id) {
    cancelRegId = id;
    const modal = document.getElementById('cancelModal');
    modal.style.display = 'flex';
}

document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

async function doCancel() {
    if (!cancelRegId) return;
    const btn = document.getElementById('confirmCancelBtn');
    btn.disabled = true;
    btn.textContent = 'Đang huỷ...';

    try {
        const result = await ktxFetch(`/testfinal/public/student/registrations/${cancelRegId}/cancel`, {
            method: 'POST',
        });
        if (result && result.success) {
            window.ktx?.showToast('Đã huỷ đơn đăng ký thành công.', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            window.ktx?.showToast(result?.message || 'Có lỗi xảy ra. Vui lòng thử lại.', 'error');
            btn.disabled = false;
            btn.textContent = 'Huỷ đơn';
        }
    } catch (e) {
        window.ktx?.showToast('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
        btn.disabled = false;
        btn.textContent = 'Huỷ đơn';
    }

    document.getElementById('cancelModal').style.display = 'none';
}
</script>
