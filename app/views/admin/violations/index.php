<?php
/**
 * Admin – Danh sách vi phạm
 * Variables: $title, $violations, $pagination, $status, $severity, $search,
 *            $statuses, $severities, $types
 */
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">⚠️ <?= htmlspecialchars($title ?? 'Quản lý vi phạm') ?></h1>
        <p class="page-subtitle">Theo dõi và xử lý các vi phạm nội quy của sinh viên</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" data-modal-open="modal-add-violation">
            ➕ Ghi nhận vi phạm
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="/testfinal/public/admin/violations" class="filter-bar-form">
        <div class="filter-group">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="🔍 Tìm theo tên sinh viên, mã SV..."
                value="<?= htmlspecialchars($search ?? '') ?>"
            >
        </div>
        <div class="filter-group">
            <select name="status" class="form-control">
                <option value="">-- Tất cả trạng thái --</option>
                <?php foreach ($statuses ?? [] as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= ($status ?? '') === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <select name="severity" class="form-control">
                <option value="">-- Tất cả mức độ --</option>
                <?php foreach ($severities ?? [] as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>" <?= (string)($severity ?? '') === (string)$key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Lọc</button>
        <a href="/testfinal/public/admin/violations" class="btn btn-outline">Đặt lại</a>
    </form>
</div>

<!-- Violations Table -->
<div class="card">
    <div class="card-body" style="padding:0">
        <?php if (!empty($violations)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Sinh viên</th>
                            <th>Loại vi phạm</th>
                            <th style="text-align:center">Điểm trừ</th>
                            <th style="text-align:center">Mức độ</th>
                            <th style="text-align:center">Trạng thái</th>
                            <th>Ngày ghi</th>
                            <th style="text-align:center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $page    = $pagination['current_page'] ?? 1;
                        $perPage = $pagination['per_page'] ?? 20;
                        $offset  = ($page - 1) * $perPage;
                        foreach ($violations as $i => $v):
                            $statusMap = [
                                'active'    => ['label' => 'Đang hiệu lực', 'class' => 'badge-danger'],
                                'appealed'  => ['label' => 'Đang khiếu nại', 'class' => 'badge-warning'],
                                'dismissed' => ['label' => 'Đã hủy',         'class' => 'badge-neutral'],
                            ];
                            $s = $statusMap[$v['status'] ?? ''] ?? ['label' => $v['status'] ?? '—', 'class' => 'badge-neutral'];

                            $severityMap = [
                                1 => ['label' => 'Nhẹ',      'class' => 'badge-info'],
                                2 => ['label' => 'Trung bình','class' => 'badge-warning'],
                                3 => ['label' => 'Nặng',     'class' => 'badge-danger'],
                            ];
                            $sev = $severityMap[(int)($v['severity'] ?? 0)] ?? ['label' => '—', 'class' => 'badge-neutral'];
                        ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td>
                                    <div style="font-weight:600"><?= htmlspecialchars($v['full_name'] ?? '—') ?></div>
                                    <div style="font-size:0.78rem;color:var(--text-muted)"><?= htmlspecialchars($v['student_code'] ?? '') ?></div>
                                </td>
                                <td><?= htmlspecialchars($v['violation_type'] ?? '—') ?></td>
                                <td style="text-align:center">
                                    <span style="color:#ef4444;font-weight:700;font-size:1rem">
                                        ⚠️ <?= (int)($v['penalty_points'] ?? 0) ?>
                                    </span>
                                </td>
                                <td style="text-align:center">
                                    <span class="badge <?= $sev['class'] ?>"><?= $sev['label'] ?></span>
                                </td>
                                <td style="text-align:center">
                                    <span class="badge <?= $s['class'] ?>"><?= $s['label'] ?></span>
                                </td>
                                <td>
                                    <?php
                                    $dt = $v['created_at'] ?? '';
                                    echo $dt ? htmlspecialchars(date('d/m/Y', strtotime($dt))) : '—';
                                    ?>
                                </td>
                                <td style="text-align:center">
                                    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                                        <a href="/testfinal/public/admin/violations/<?= (int)$v['id'] ?>"
                                           class="btn btn-ghost btn-sm">👁 Chi tiết</a>
                                        <?php if (($v['status'] ?? '') !== 'dismissed'): ?>
                                            <form method="POST"
                                                  action="/testfinal/public/admin/violations/<?= (int)$v['id'] ?>/dismiss"
                                                  onsubmit="return confirm('Xác nhận hủy vi phạm này?')"
                                                  style="display:inline">
                                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
                                                <button type="submit" class="btn btn-outline btn-sm">🚫 Hủy</button>
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
                <div class="empty-state-icon">✅</div>
                <div class="empty-state-title">Không có vi phạm nào</div>
                <div class="empty-state-desc">Không tìm thấy bản ghi vi phạm phù hợp với bộ lọc hiện tại.</div>
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
            'search'   => $search   ?? '',
            'status'   => $status   ?? '',
            'severity' => $severity ?? '',
        ]));
        ?>
        <?php if ($cur > 1): ?>
            <a href="/testfinal/public/admin/violations?page=<?= $cur - 1 ?>&<?= $qs ?>" class="page-link">‹ Trước</a>
        <?php endif; ?>
        <?php for ($p = max(1, $cur - 2); $p <= min($total, $cur + 2); $p++): ?>
            <a href="/testfinal/public/admin/violations?page=<?= $p ?>&<?= $qs ?>"
               class="page-link <?= $p === $cur ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($cur < $total): ?>
            <a href="/testfinal/public/admin/violations?page=<?= $cur + 1 ?>&<?= $qs ?>" class="page-link">Sau ›</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Modal: Ghi nhận vi phạm -->
<div class="modal-overlay" id="modal-add-violation" data-modal-close="modal-add-violation" style="display:none">
    <div class="modal" onclick="event.stopPropagation()">
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <h3 class="card-title">⚠️ Ghi nhận vi phạm</h3>
                <button class="btn btn-ghost btn-sm" data-modal-close="modal-add-violation">✕</button>
            </div>
            <div class="card-body">
                <form id="form-add-violation" novalidate>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

                    <div class="form-group">
                        <label class="form-label" for="fv-student-id">
                            Mã sinh viên (ID) <span style="color:#ef4444">*</span>
                        </label>
                        <input
                            type="number"
                            id="fv-student-id"
                            name="student_id"
                            class="form-control"
                            placeholder="Nhập ID sinh viên..."
                            min="1"
                            required
                        >
                        <div class="form-error" id="err-student-id" style="display:none"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fv-type">
                            Loại vi phạm <span style="color:#ef4444">*</span>
                        </label>
                        <select id="fv-type" name="violation_type" class="form-control" required>
                            <option value="">-- Chọn loại vi phạm --</option>
                            <?php foreach ($types ?? [] as $t): ?>
                                <option value="<?= htmlspecialchars(is_array($t) ? ($t['id'] ?? $t['name'] ?? $t) : $t) ?>">
                                    <?= htmlspecialchars(is_array($t) ? ($t['name'] ?? $t['label'] ?? $t) : $t) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-error" id="err-type" style="display:none"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fv-desc">Mô tả chi tiết</label>
                        <textarea
                            id="fv-desc"
                            name="description"
                            class="form-control"
                            rows="3"
                            placeholder="Mô tả hành vi vi phạm..."
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fv-points">
                            Điểm trừ tùy chỉnh
                        </label>
                        <input
                            type="number"
                            id="fv-points"
                            name="override_points"
                            class="form-control"
                            placeholder="Để trống = áp dụng mặc định theo loại"
                            min="0"
                            max="100"
                        >
                        <div class="form-hint">Nếu để trống, hệ thống sẽ dùng điểm trừ mặc định của loại vi phạm.</div>
                    </div>

                    <div id="form-add-violation-alert" style="display:none"></div>
                </form>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button class="btn btn-outline" data-modal-close="modal-add-violation">Hủy</button>
                <button class="btn btn-primary" id="btn-submit-violation">💾 Ghi nhận</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // Modal open/close
    document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-modal-open');
            var modal = document.getElementById(id);
            if (modal) modal.style.display = 'flex';
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-modal-close');
            var modal = document.getElementById(id);
            if (modal) modal.style.display = 'none';
        });
    });

    // Submit violation via ktxFetch
    var submitBtn = document.getElementById('btn-submit-violation');
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            var form    = document.getElementById('form-add-violation');
            var alert   = document.getElementById('form-add-violation-alert');
            var errSid  = document.getElementById('err-student-id');
            var errType = document.getElementById('err-type');

            // Reset errors
            errSid.style.display  = 'none';
            errType.style.display = 'none';
            alert.style.display   = 'none';

            var data = {
                _csrf:          form.querySelector('[name="_csrf"]').value,
                student_id:     form.querySelector('[name="student_id"]').value.trim(),
                violation_type: form.querySelector('[name="violation_type"]').value,
                description:    form.querySelector('[name="description"]').value.trim(),
                override_points:form.querySelector('[name="override_points"]').value.trim(),
            };

            var valid = true;
            if (!data.student_id) {
                errSid.textContent = 'Vui lòng nhập ID sinh viên.';
                errSid.style.display = 'block';
                valid = false;
            }
            if (!data.violation_type) {
                errType.textContent = 'Vui lòng chọn loại vi phạm.';
                errType.style.display = 'block';
                valid = false;
            }
            if (!valid) return;

            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Đang lưu...';

            if (!data.override_points) delete data.override_points;

            ktxFetch('POST', '/testfinal/public/api/violations', data)
                .then(function (res) {
                    if (res && res.success) {
                        alert.innerHTML = '<div class="alert alert-success">✅ Ghi nhận vi phạm thành công!</div>';
                        alert.style.display = 'block';
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        throw new Error((res && res.message) ? res.message : 'Có lỗi xảy ra.');
                    }
                })
                .catch(function (err) {
                    alert.innerHTML = '<div class="alert alert-danger">❌ ' + (err.message || 'Có lỗi xảy ra.') + '</div>';
                    alert.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.textContent = '💾 Ghi nhận';
                });
        });
    }
})();
</script>
