<?php
// Views: student/registrations/create.php
// Variables: $title, $buildings, $_old, $_errors, $_csrfToken, $current_semester

$buildings       = $buildings ?? [];
$old             = $_old ?? [];
$errors          = $_errors ?? [];
$csrfToken       = $_csrfToken ?? '';
$currentSemester = $current_semester ?? [];

$semLabel = '';
if (!empty($currentSemester)) {
    $sem = $currentSemester['semester'] ?? '';
    $yr  = $currentSemester['year'] ?? '';
    $semLabel = match((string)$sem) {
        '1' => "Học kỳ 1",
        '2' => "Học kỳ 2",
        '3' => "Học kỳ hè",
        default => "Học kỳ $sem"
    };
    if ($yr) $semLabel .= " — Năm học $yr";
}

function fieldError(array $errors, string $field): string {
    if (isset($errors[$field])) {
        return '<div class="form-error">⚠️ ' . htmlspecialchars($errors[$field]) . '</div>';
    }
    return '';
}

function oldValue(array $old, string $field, string $default = ''): string {
    return htmlspecialchars($old[$field] ?? $default);
}
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">➕ Đăng ký phòng mới</h1>
        <p class="page-subtitle">Điền thông tin để đăng ký ở ký túc xá</p>
    </div>
    <div class="page-actions">
        <a href="/testfinal/public/student/registrations" class="btn btn-ghost">← Quay lại</a>
    </div>
</div>

<div style="max-width:600px;margin:0 auto;">

    <!-- Semester Info Alert -->
    <?php if ($semLabel): ?>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px 18px;margin-bottom:24px;display:flex;gap:12px;align-items:flex-start;">
            <span style="font-size:22px;flex-shrink:0;">📅</span>
            <div>
                <div style="font-weight:700;color:#1e40af;font-size:14px;margin-bottom:2px;">Học kỳ đang nhận đăng ký</div>
                <div style="color:#1d4ed8;font-size:15px;font-weight:600;"><?= htmlspecialchars($semLabel) ?></div>
                <div style="color:#3b82f6;font-size:12.5px;margin-top:4px;">Đơn của bạn sẽ được xử lý theo học kỳ này.</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Form Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📝 Thông tin đăng ký</h3>
            <p style="font-size:13px;color:#6b7280;margin:4px 0 0 0;">Tất cả các trường đều không bắt buộc — BQL sẽ phân phòng phù hợp cho bạn.</p>
        </div>
        <div class="card-body">
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;gap:10px;align-items:flex-start;">
                    <span style="font-size:18px;">❌</span>
                    <div style="color:#b91c1c;font-size:14px;"><?= htmlspecialchars($errors['general']) ?></div>
                </div>
            <?php endif; ?>

            <form action="/testfinal/public/student/registrations" method="POST" id="registrationForm">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="_method" value="POST">

                <!-- Preferred Building -->
                <div class="form-group">
                    <label class="form-label" for="preferred_building_id">
                        🏢 Tòa nhà ưu tiên
                        <span style="font-weight:400;color:#94a3b8;font-size:12px;">(Tuỳ chọn)</span>
                    </label>
                    <select class="form-control <?= isset($errors['preferred_building_id']) ? 'is-invalid' : '' ?>"
                            id="preferred_building_id"
                            name="preferred_building_id">
                        <option value="">— Không có ưu tiên —</option>
                        <?php foreach ($buildings as $building): ?>
                            <option value="<?= (int)($building['id'] ?? 0) ?>"
                                <?= (oldValue($old, 'preferred_building_id') == ($building['id'] ?? '')) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($building['name'] ?? '') ?>
                                <?php if (!empty($building['gender_type'])): ?>
                                    (<?= htmlspecialchars($building['gender_type']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= fieldError($errors, 'preferred_building_id') ?>
                    <div class="form-hint">Chọn tòa nhà bạn muốn ở nếu có. BQL sẽ cố gắng đáp ứng theo điều kiện thực tế.</div>
                </div>

                <!-- Preferred Room Type -->
                <div class="form-group">
                    <label class="form-label" for="preferred_room_type">
                        🛏️ Loại phòng ưu tiên
                        <span style="font-weight:400;color:#94a3b8;font-size:12px;">(Tuỳ chọn)</span>
                    </label>
                    <select class="form-control <?= isset($errors['preferred_room_type']) ? 'is-invalid' : '' ?>"
                            id="preferred_room_type"
                            name="preferred_room_type">
                        <option value="">— Không có ưu tiên —</option>
                        <option value="standard"    <?= oldValue($old, 'preferred_room_type') === 'standard'    ? 'selected' : '' ?>>
                            Phòng thường (Standard)
                        </option>
                        <option value="deluxe"      <?= oldValue($old, 'preferred_room_type') === 'deluxe'      ? 'selected' : '' ?>>
                            Phòng cao cấp (Deluxe)
                        </option>
                        <option value="ac_standard" <?= oldValue($old, 'preferred_room_type') === 'ac_standard' ? 'selected' : '' ?>>
                            Phòng thường có điều hoà (AC Standard)
                        </option>
                        <option value="ac_deluxe"   <?= oldValue($old, 'preferred_room_type') === 'ac_deluxe'   ? 'selected' : '' ?>>
                            Phòng cao cấp có điều hoà (AC Deluxe)
                        </option>
                    </select>
                    <?= fieldError($errors, 'preferred_room_type') ?>
                    <div class="form-hint">Loại phòng sẽ ảnh hưởng đến mức giá thuê hàng tháng.</div>
                </div>

                <!-- Room Type Info Cards -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;">
                    <div style="background:#f8fafc;border-radius:10px;padding:12px;border:1px solid #e2e8f0;">
                        <div style="font-weight:700;font-size:13px;color:#1e293b;margin-bottom:4px;">🛏️ Phòng thường</div>
                        <div style="font-size:12px;color:#64748b;">Phòng cơ bản, không có điều hoà. Phù hợp với sinh viên tiết kiệm.</div>
                    </div>
                    <div style="background:#f8fafc;border-radius:10px;padding:12px;border:1px solid #e2e8f0;">
                        <div style="font-weight:700;font-size:13px;color:#1e293b;margin-bottom:4px;">⭐ Phòng cao cấp</div>
                        <div style="font-size:12px;color:#64748b;">Tiện nghi đầy đủ hơn, diện tích rộng hơn phòng thường.</div>
                    </div>
                    <div style="background:#eff6ff;border-radius:10px;padding:12px;border:1px solid #bfdbfe;">
                        <div style="font-weight:700;font-size:13px;color:#1e40af;margin-bottom:4px;">❄️ AC Standard</div>
                        <div style="font-size:12px;color:#3b82f6;">Phòng thường có trang bị điều hoà nhiệt độ.</div>
                    </div>
                    <div style="background:#eff6ff;border-radius:10px;padding:12px;border:1px solid #bfdbfe;">
                        <div style="font-weight:700;font-size:13px;color:#1e40af;margin-bottom:4px;">❄️⭐ AC Deluxe</div>
                        <div style="font-size:12px;color:#3b82f6;">Phòng cao cấp có điều hoà, tiện nghi tốt nhất.</div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label class="form-label" for="notes">
                        📝 Ghi chú / Yêu cầu đặc biệt
                        <span style="font-weight:400;color:#94a3b8;font-size:12px;">(Tuỳ chọn)</span>
                    </label>
                    <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>"
                              id="notes"
                              name="notes"
                              rows="4"
                              maxlength="500"
                              placeholder="Ví dụ: Tôi có nhu cầu đặc biệt về sức khoẻ, muốn ở cùng bạn cùng lớp..."
                              oninput="updateCharCount(this)"><?= oldValue($old, 'notes') ?></textarea>
                    <?= fieldError($errors, 'notes') ?>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
                        <div class="form-hint">Ghi rõ yêu cầu đặc biệt (nếu có) để BQL xem xét.</div>
                        <span id="charCount" style="font-size:12px;color:#94a3b8;">0/500</span>
                    </div>
                </div>

                <!-- Notice -->
                <div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;margin-bottom:24px;font-size:13px;color:#713f12;display:flex;gap:10px;">
                    <span style="flex-shrink:0;">💡</span>
                    <div>
                        <strong>Lưu ý:</strong> Sau khi gửi đơn, BQL sẽ xem xét và phân phòng trong thời gian sớm nhất.
                        Bạn sẽ nhận được thông báo qua hệ thống khi có kết quả.
                        Mỗi sinh viên chỉ được có <strong>một đơn đăng ký</strong> đang chờ xử lý tại một thời điểm.
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary" id="submitBtn" style="flex:1;min-width:120px;">
                        ✅ Gửi đơn đăng ký
                    </button>
                    <a href="/testfinal/public/student/registrations" class="btn btn-outline" style="flex:1;min-width:120px;text-align:center;">
                        ✕ Huỷ bỏ
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Help Card -->
    <div class="card mt-16" style="border:1px dashed #cbd5e1;background:#f8fafc;">
        <div class="card-body" style="padding:16px 20px;">
            <h4 style="font-weight:700;font-size:14px;color:#374151;margin-bottom:10px;">❓ Cần hỗ trợ?</h4>
            <p style="font-size:13px;color:#6b7280;margin:0;">
                Liên hệ Ban Quản lý Ký túc xá tại phòng 101 — Toà A, hoặc gọi <strong>028 xxxx xxxx</strong>
                trong giờ hành chính (7:30 – 17:00, Thứ Hai đến Thứ Sáu).
            </p>
        </div>
    </div>

</div>

<script>
function updateCharCount(el) {
    const count = el.value.length;
    const el2 = document.getElementById('charCount');
    if (el2) el2.textContent = count + '/500';
    el2.style.color = count > 450 ? '#ef4444' : '#94a3b8';
}

// Initialize count
const notesEl = document.getElementById('notes');
if (notesEl) updateCharCount(notesEl);

// Prevent double submit
document.getElementById('registrationForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Đang gửi...';
});
</script>
