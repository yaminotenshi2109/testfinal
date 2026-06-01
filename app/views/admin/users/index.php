<?php
/**
 * app/views/admin/users/index.php
 * ─────────────────────────────────────────────────────────────
 *  Danh sách người dùng — Admin panel
 *  Variables: $title, $users[], $pagination[]
 *  Supports: search, role filter, status filter, add/edit/delete modal
 * ─────────────────────────────────────────────────────────────
 */

$currentPage  = (int)($pagination['current_page'] ?? 1);
$lastPage     = (int)($pagination['last_page']    ?? 1);
$perPage      = (int)($pagination['per_page']     ?? 15);
$total        = (int)($pagination['total']        ?? 0);
$from         = (int)($pagination['from']         ?? 0);
$to           = (int)($pagination['to']           ?? 0);

$currentQ      = htmlspecialchars($_GET['q']      ?? '');
$currentRole   = htmlspecialchars($_GET['role']   ?? '');
$currentStatus = htmlspecialchars($_GET['status'] ?? '');
?>

<!-- ── Page Header ──────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">👤 Quản lý tài khoản</h1>
        <p class="page-subtitle">Tổng cộng <?= number_format($total) ?> tài khoản trong hệ thống</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-outline btn-sm" onclick="exportUsers()">
            📥 Xuất Excel
        </button>
        <button class="btn btn-primary" data-modal-open="modalAddUser">
            ➕ Thêm người dùng
        </button>
    </div>
</div>

<!-- ── Flash messages ───────────────────────────────────────── -->
<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success mb-16">
        <span class="alert-icon">✅</span>
        <div class="alert-content"><div class="alert-msg"><?= htmlspecialchars($_SESSION['flash_success']) ?></div></div>
        <button class="alert-close" onclick="this.closest('.alert').remove()">×</button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-error mb-16">
        <span class="alert-icon">❌</span>
        <div class="alert-content"><div class="alert-msg"><?= htmlspecialchars($_SESSION['flash_error']) ?></div></div>
        <button class="alert-close" onclick="this.closest('.alert').remove()">×</button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- ── Table Card ───────────────────────────────────────────── -->
<div class="card">

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-search">
            <span class="search-icon">🔍</span>
            <input type="text"
                   id="searchInput"
                   class="form-control"
                   placeholder="Tìm username, email..."
                   value="<?= $currentQ ?>"
                   onkeydown="if(event.key==='Enter') applyFilters()">
        </div>

        <select id="roleFilter" class="form-control" style="width:auto" onchange="applyFilters()">
            <option value="">Tất cả vai trò</option>
            <option value="admin"   <?= $currentRole === 'admin'   ? 'selected' : '' ?>>🛡️ Quản trị viên</option>
            <option value="student" <?= $currentRole === 'student' ? 'selected' : '' ?>>🎓 Sinh viên</option>
        </select>

        <select id="statusFilter" class="form-control" style="width:auto" onchange="applyFilters()">
            <option value="">Tất cả trạng thái</option>
            <option value="active"   <?= $currentStatus === 'active'   ? 'selected' : '' ?>>✅ Hoạt động</option>
            <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>🔴 Không hoạt động</option>
        </select>

        <?php if ($currentQ || $currentRole || $currentStatus): ?>
            <a href="/testfinal/public/admin/users" class="btn btn-ghost btn-sm">✕ Xóa bộ lọc</a>
        <?php endif; ?>

        <span style="margin-left:auto;font-size:12px;color:var(--txt-muted)">
            Hiển thị <?= $from ?>–<?= $to ?> / <?= number_format($total) ?>
        </span>
    </div>

    <!-- Table -->
    <div class="table-wrapper" style="border:none;border-radius:0;box-shadow:none">
        <table>
            <thead>
                <tr>
                    <th style="width:40px">#</th>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Họ và tên</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th style="width:120px;text-align:center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $i => $user): ?>
                        <?php
                            $uid     = (int)($user['id'] ?? 0);
                            $role    = $user['role']   ?? 'student';
                            $status  = $user['status'] ?? 'active';
                            $initial = mb_strtoupper(mb_substr($user['username'] ?? 'U', 0, 1));
                            $rowNum  = $from + $i;
                        ?>
                        <tr>
                            <td style="color:var(--txt-muted);font-size:12px"><?= $rowNum ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:9px">
                                    <div class="avatar avatar-sm"><?= $initial ?></div>
                                    <div>
                                        <div style="font-weight:600"><?= htmlspecialchars($user['username'] ?? '') ?></div>
                                        <?php if (!empty($user['student_code'])): ?>
                                            <div class="sub">MSV: <?= htmlspecialchars($user['student_code']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--txt-secondary)"><?= htmlspecialchars($user['email'] ?? '—') ?></td>
                            <td>
                                <?php if (!empty($user['full_name'])): ?>
                                    <span><?= htmlspecialchars($user['full_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($role === 'admin'): ?>
                                    <span class="badge badge-purple">🛡️ Quản trị viên</span>
                                <?php else: ?>
                                    <span class="badge badge-info">🎓 Sinh viên</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($status === 'active'): ?>
                                    <span class="badge badge-success">✅ Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral">🔴 Vô hiệu hóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size:12px;color:var(--txt-muted)">
                                    <?= !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '—' ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;gap:4px;justify-content:center">
                                    <button class="btn btn-ghost btn-sm"
                                            title="Sửa"
                                            onclick="openEditUserModal(<?= $uid ?>)">
                                        ✏️
                                    </button>
                                    <button class="btn btn-ghost btn-sm"
                                            title="Đặt lại mật khẩu"
                                            onclick="openResetPasswordModal(<?= $uid ?>, '<?= htmlspecialchars(addslashes($user['username'] ?? '')) ?>')">
                                        🔑
                                    </button>
                                    <button class="btn btn-danger-outline btn-sm"
                                            title="Xóa"
                                            onclick="deleteUser(<?= $uid ?>, '<?= htmlspecialchars(addslashes($user['username'] ?? '')) ?>')">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">👤</div>
                                <div class="empty-title">Không tìm thấy tài khoản</div>
                                <div class="empty-msg">
                                    <?php if ($currentQ || $currentRole || $currentStatus): ?>
                                        Không có kết quả phù hợp với bộ lọc hiện tại.
                                        <a href="/testfinal/public/admin/users">Xóa bộ lọc</a>
                                    <?php else: ?>
                                        Chưa có tài khoản nào trong hệ thống.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($lastPage > 1): ?>
        <div class="card-footer" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <span class="pagination-info">
                Trang <?= $currentPage ?> / <?= $lastPage ?> &nbsp;·&nbsp; <?= number_format($total) ?> bản ghi
            </span>
            <div class="pagination" style="margin-left:auto">
                <!-- Prev -->
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>&q=<?= $currentQ ?>&role=<?= $currentRole ?>&status=<?= $currentStatus ?>"
                       class="page-link">‹</a>
                <?php else: ?>
                    <span class="page-link disabled">‹</span>
                <?php endif; ?>

                <!-- Pages -->
                <?php
                    $startP = max(1, $currentPage - 2);
                    $endP   = min($lastPage, $currentPage + 2);
                ?>
                <?php if ($startP > 1): ?>
                    <a href="?page=1&q=<?= $currentQ ?>&role=<?= $currentRole ?>&status=<?= $currentStatus ?>" class="page-link">1</a>
                    <?php if ($startP > 2): ?><span class="page-link disabled">…</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $startP; $p <= $endP; $p++): ?>
                    <a href="?page=<?= $p ?>&q=<?= $currentQ ?>&role=<?= $currentRole ?>&status=<?= $currentStatus ?>"
                       class="page-link <?= $p === $currentPage ? 'active' : '' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($endP < $lastPage): ?>
                    <?php if ($endP < $lastPage - 1): ?><span class="page-link disabled">…</span><?php endif; ?>
                    <a href="?page=<?= $lastPage ?>&q=<?= $currentQ ?>&role=<?= $currentRole ?>&status=<?= $currentStatus ?>"
                       class="page-link"><?= $lastPage ?></a>
                <?php endif; ?>

                <!-- Next -->
                <?php if ($currentPage < $lastPage): ?>
                    <a href="?page=<?= $currentPage + 1 ?>&q=<?= $currentQ ?>&role=<?= $currentRole ?>&status=<?= $currentStatus ?>"
                       class="page-link">›</a>
                <?php else: ?>
                    <span class="page-link disabled">›</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div><!-- /.card -->


<!-- ══════════════════════════════════════════════════════════ -->
<!--  MODAL: Thêm người dùng                                   -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalAddUser">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">➕ Thêm người dùng mới</div>
            <button class="modal-close" data-modal-close="modalAddUser">×</button>
        </div>
        <form id="formAddUser" onsubmit="submitAddUser(event)">
            <div class="modal-body">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tên đăng nhập <span class="req">*</span></label>
                        <input type="text" name="username" id="add_username"
                               class="form-control" placeholder="sv001" required
                               autocomplete="off">
                        <div class="form-error d-none" id="add_username_err"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="req">*</span></label>
                        <input type="email" name="email" id="add_email"
                               class="form-control" placeholder="sv@ktx.edu.vn" required>
                        <div class="form-error d-none" id="add_email_err"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mật khẩu <span class="req">*</span></label>
                        <input type="password" name="password" id="add_password"
                               class="form-control" placeholder="Tối thiểu 8 ký tự" required>
                        <div class="form-error d-none" id="add_password_err"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Xác nhận mật khẩu <span class="req">*</span></label>
                        <input type="password" name="password_confirm" id="add_password_confirm"
                               class="form-control" placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Vai trò <span class="req">*</span></label>
                        <select name="role" id="add_role" class="form-control"
                                required onchange="toggleStudentFields('add')">
                            <option value="student">🎓 Sinh viên</option>
                            <option value="admin">🛡️ Quản trị viên</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-control">
                            <option value="active">✅ Hoạt động</option>
                            <option value="inactive">🔴 Vô hiệu hóa</option>
                        </select>
                    </div>
                </div>

                <!-- Student-only fields -->
                <div id="add_student_fields">
                    <div style="margin:4px 0 16px;border-top:1px solid var(--border);padding-top:16px">
                        <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--txt-muted);margin-bottom:14px">
                            📋 Thông tin sinh viên
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="full_name" class="form-control"
                                   placeholder="Nguyễn Văn A">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mã sinh viên</label>
                            <input type="text" name="student_code" class="form-control"
                                   placeholder="2023XXXXX">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control"
                                   placeholder="0123 456 789">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Giới tính</label>
                            <select name="gender" class="form-control">
                                <option value="">-- Chọn --</option>
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div><!-- /.modal-body -->
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modalAddUser">Hủy</button>
                <button type="submit" class="btn btn-primary" id="btnAddUser">
                    <span>➕ Tạo tài khoản</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  MODAL: Sửa người dùng                                    -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalEditUser">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">✏️ Sửa tài khoản</div>
            <button class="modal-close" data-modal-close="modalEditUser">×</button>
        </div>
        <form id="formEditUser" onsubmit="submitEditUser(event)">
            <div class="modal-body">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
                <input type="hidden" name="user_id"    id="edit_user_id">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tên đăng nhập</label>
                        <input type="text" id="edit_username" class="form-control" disabled
                               style="background:#f8fafc;cursor:not-allowed">
                        <div class="form-hint">Tên đăng nhập không thể thay đổi.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="req">*</span></label>
                        <input type="email" name="email" id="edit_email"
                               class="form-control" required>
                        <div class="form-error d-none" id="edit_email_err"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Vai trò</label>
                        <select name="role" id="edit_role" class="form-control">
                            <option value="student">🎓 Sinh viên</option>
                            <option value="admin">🛡️ Quản trị viên</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="active">✅ Hoạt động</option>
                            <option value="inactive">🔴 Vô hiệu hóa</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Họ và tên</label>
                    <input type="text" name="full_name" id="edit_full_name"
                           class="form-control" placeholder="Nguyễn Văn A">
                </div>

            </div><!-- /.modal-body -->
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modalEditUser">Hủy</button>
                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  MODAL: Đặt lại mật khẩu                                  -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalResetPassword">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <div class="modal-title">🔑 Đặt lại mật khẩu</div>
            <button class="modal-close" data-modal-close="modalResetPassword">×</button>
        </div>
        <form id="formResetPassword" onsubmit="submitResetPassword(event)">
            <div class="modal-body">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
                <input type="hidden" name="user_id" id="reset_user_id">

                <div class="alert alert-warning" style="margin-bottom:16px">
                    <span class="alert-icon">⚠️</span>
                    <div class="alert-content">
                        <div class="alert-msg">
                            Mật khẩu của <strong id="reset_username_label"></strong> sẽ được thay đổi.
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mật khẩu mới <span class="req">*</span></label>
                    <input type="password" name="password" class="form-control"
                           placeholder="Tối thiểu 8 ký tự" required minlength="8">
                    <div class="form-hint">Mật khẩu tối thiểu 8 ký tự.</div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-check">
                        <input type="checkbox" name="notify_email" checked>
                        <span style="font-size:13px">Gửi thông báo qua email</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modalResetPassword">Hủy</button>
                <button type="submit" class="btn btn-primary">🔑 Đặt lại</button>
            </div>
        </form>
    </div>
</div>


<script>
/* ── Modal helpers ─────────────────────────────────────────── */
function openModal(id)  {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

// Wire up data-modal-open / data-modal-close buttons
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(btn.getAttribute('data-modal-open')); });
    });
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal(btn.getAttribute('data-modal-close')); });
    });
    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });
});

/* ── Filter helpers ────────────────────────────────────────── */
function applyFilters() {
    const q      = document.getElementById('searchInput').value;
    const role   = document.getElementById('roleFilter').value;
    const status = document.getElementById('statusFilter').value;
    window.location.href = '?q=' + encodeURIComponent(q)
                         + '&role=' + encodeURIComponent(role)
                         + '&status=' + encodeURIComponent(status)
                         + '&page=1';
}

/* ── Toggle student fields visibility ──────────────────────── */
function toggleStudentFields(prefix) {
    const role      = document.getElementById(prefix + '_role').value;
    const container = document.getElementById(prefix + '_student_fields');
    if (container) container.style.display = role === 'student' ? 'block' : 'none';
}

/* ── Add user ───────────────────────────────────────────────── */
function submitAddUser(e) {
    e.preventDefault();
    const form = document.getElementById('formAddUser');
    const btn  = document.getElementById('btnAddUser');
    const data = new FormData(form);

    // Clear previous errors
    document.querySelectorAll('#formAddUser .form-error').forEach(el => {
        el.textContent = ''; el.classList.add('d-none');
    });

    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> Đang tạo...';

    fetch('/testfinal/public/api/users', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': data.get('_csrf_token') },
        body: JSON.stringify(Object.fromEntries(data))
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            closeModal('modalAddUser');
            showKtxToast('success', '✅ Tạo tài khoản thành công!');
            setTimeout(() => location.reload(), 1200);
        } else {
            if (json.errors) {
                Object.entries(json.errors).forEach(([field, msgs]) => {
                    const el = document.getElementById('add_' + field + '_err');
                    if (el) { el.textContent = msgs[0]; el.classList.remove('d-none'); }
                });
            } else {
                showKtxToast('error', json.message || 'Đã có lỗi xảy ra.');
            }
        }
    })
    .catch(err => showKtxToast('error', 'Lỗi kết nối: ' + err.message))
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<span>➕ Tạo tài khoản</span>';
    });
}

/* ── Open edit modal ────────────────────────────────────────── */
function openEditUserModal(id) {
    fetch('/testfinal/public/api/users/' + id)
    .then(r => r.json())
    .then(json => {
        if (!json.success) { showKtxToast('error', 'Không tải được thông tin người dùng.'); return; }
        const u = json.data;
        document.getElementById('edit_user_id').value   = u.id;
        document.getElementById('edit_username').value  = u.username;
        document.getElementById('edit_email').value     = u.email;
        document.getElementById('edit_role').value      = u.role;
        document.getElementById('edit_status').value    = u.status;
        document.getElementById('edit_full_name').value = u.full_name || '';
        openModal('modalEditUser');
    })
    .catch(() => showKtxToast('error', 'Lỗi kết nối.'));
}

/* ── Submit edit ────────────────────────────────────────────── */
function submitEditUser(e) {
    e.preventDefault();
    const form   = document.getElementById('formEditUser');
    const data   = new FormData(form);
    const userId = data.get('user_id');

    document.querySelectorAll('#formEditUser .form-error').forEach(el => {
        el.textContent = ''; el.classList.add('d-none');
    });

    fetch('/testfinal/public/api/users/' + userId, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': data.get('_csrf_token') },
        body: JSON.stringify(Object.fromEntries(data))
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            closeModal('modalEditUser');
            showKtxToast('success', '✅ Cập nhật tài khoản thành công!');
            setTimeout(() => location.reload(), 1200);
        } else {
            if (json.errors) {
                Object.entries(json.errors).forEach(([field, msgs]) => {
                    const el = document.getElementById('edit_' + field + '_err');
                    if (el) { el.textContent = msgs[0]; el.classList.remove('d-none'); }
                });
            } else {
                showKtxToast('error', json.message || 'Đã có lỗi xảy ra.');
            }
        }
    })
    .catch(err => showKtxToast('error', 'Lỗi kết nối: ' + err.message));
}

/* ── Delete user ────────────────────────────────────────────── */
function deleteUser(id, username) {
    if (!confirm('Bạn có chắc muốn xóa tài khoản "' + username + '"?\nHành động này không thể hoàn tác!')) return;

    fetch('/testfinal/public/api/users/' + id, { method: 'DELETE' })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            showKtxToast('success', '🗑️ Đã xóa tài khoản ' + username);
            setTimeout(() => location.reload(), 1200);
        } else {
            showKtxToast('error', json.message || 'Xóa thất bại.');
        }
    })
    .catch(err => showKtxToast('error', 'Lỗi kết nối: ' + err.message));
}

/* ── Reset password ─────────────────────────────────────────── */
function openResetPasswordModal(id, username) {
    document.getElementById('reset_user_id').value       = id;
    document.getElementById('reset_username_label').textContent = username;
    document.getElementById('formResetPassword').reset();
    document.getElementById('reset_user_id').value = id;
    openModal('modalResetPassword');
}

function submitResetPassword(e) {
    e.preventDefault();
    const form   = document.getElementById('formResetPassword');
    const data   = new FormData(form);
    const userId = data.get('user_id');

    fetch('/testfinal/public/api/users/' + userId + '/reset-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': data.get('_csrf_token') },
        body: JSON.stringify(Object.fromEntries(data))
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            closeModal('modalResetPassword');
            showKtxToast('success', '🔑 Đặt lại mật khẩu thành công!');
        } else {
            showKtxToast('error', json.message || 'Đặt lại mật khẩu thất bại.');
        }
    })
    .catch(err => showKtxToast('error', 'Lỗi kết nối: ' + err.message));
}

/* ── Export ─────────────────────────────────────────────────── */
function exportUsers() {
    const q      = document.getElementById('searchInput')?.value ?? '';
    const role   = document.getElementById('roleFilter')?.value  ?? '';
    const status = document.getElementById('statusFilter')?.value ?? '';
    window.location.href = '/testfinal/public/api/users/export?q=' + encodeURIComponent(q)
                         + '&role=' + encodeURIComponent(role)
                         + '&status=' + encodeURIComponent(status);
}

/* ── Toast helper (fallback if window.ktx not available) ────── */
function showKtxToast(type, msg) {
    if (window.ktx && window.ktx.toast) { window.ktx.toast(type, msg); return; }
    // Simple inline toast
    const toast = document.createElement('div');
    toast.style.cssText = [
        'position:fixed;bottom:24px;right:24px;z-index:9999',
        'padding:12px 20px;border-radius:8px;font-size:13.5px;font-weight:600',
        'color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.25)',
        'animation:slideUp .25s ease',
        'max-width:360px',
        type === 'success' ? 'background:#10b981' : 'background:#ef4444'
    ].join(';');
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}
</script>
