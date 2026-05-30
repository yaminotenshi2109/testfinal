<?php
/**
 * app/views/admin/users/index.php
 * ─────────────────────────────────────────────────────────────
 *  Danh sách người dùng với AJAX CRUD
 *  Hỗ trợ: tạo, sửa, xóa, cập nhật trạng thái, xuất Excel
 * ─────────────────────────────────────────────────────────────
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý tài khoản</h1>
        <p class="page-subtitle">Tổng số: <?= $pagination['total'] ?? 0 ?> tài khoản</p>
    </div>
    <button class="btn btn-primary" onclick="openUserModal()">
        <i class="ti ti-plus"></i>
        <span>Tạo tài khoản mới</span>
    </button>
</div>

<!-- Search & Filters -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; gap: 12px;">
        <div style="display: flex; gap: 12px; flex: 1;">
            <input 
                type="text" 
                id="searchInput"
                placeholder="Tìm kiếm (username, email, tên)..." 
                style="flex: 1; padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;"
                onkeyup="filterUsers()"
            >
            <select 
                id="roleFilter"
                style="padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;"
                onchange="filterUsers()"
            >
                <option value="">Tất cả vai trò</option>
                <option value="admin">Quản trị viên</option>
                <option value="student">Sinh viên</option>
            </select>
            <select 
                id="statusFilter"
                style="padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;"
                onchange="filterUsers()"
            >
                <option value="">Tất cả trạng thái</option>
                <option value="active">Hoạt động</option>
                <option value="inactive">Không hoạt động</option>
            </select>
        </div>
        <button class="btn btn-sm" onclick="exportUsers()">
            <i class="ti ti-download"></i>
            <span>Xuất Excel</span>
        </button>
    </div>
</div>

<!-- Bulk Actions -->
<div id="bulkActions" style="display: none; margin-bottom: 1rem; padding: 1rem; background: #E6F1FB; border-radius: 4px; align-items: center; gap: 12px;">
    <span id="bulkCount" style="font-weight: 500;"></span>
    <button class="btn btn-sm" onclick="bulkSetStatus('active')">
        <i class="ti ti-check"></i>
        <span>Kích hoạt</span>
    </button>
    <button class="btn btn-sm" onclick="bulkSetStatus('inactive')">
        <i class="ti ti-circle-x"></i>
        <span>Vô hiệu hóa</span>
    </button>
    <button class="btn btn-sm" style="margin-left: auto;" onclick="clearSelection()">
        <i class="ti ti-x"></i>
        <span>Hủy</span>
    </button>
</div>

<!-- User Table -->
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th style="width: 40px;">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                </th>
                <th>Username</th>
                <th>Email</th>
                <th>Tên / Mã SV</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Tạo lúc</th>
                <th style="width: 120px;">Hành động</th>
            </tr>
        </thead>
        <tbody id="userTableBody">
            <?php foreach (($users ?? []) as $user): ?>
                <tr class="user-row" data-user-id="<?= $user['id'] ?>">
                    <td>
                        <input type="checkbox" class="user-checkbox" onchange="updateBulkActions()">
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                    </td>
                    <td style="font-size: 13px; color: var(--color-text-muted);">
                        <?= htmlspecialchars($user['email']) ?>
                    </td>
                    <td style="font-size: 13px;">
                        <div><?= htmlspecialchars($user['student_name'] ?? '-') ?></div>
                        <div style="color: var(--color-text-muted); font-size: 11px;">
                            <?= htmlspecialchars($user['student_code'] ?? '-') ?>
                        </div>
                    </td>
                    <td>
                        <span style="font-size: 12px; padding: 0.25rem 0.75rem; background: #E6F1FB; color: #185FA5; border-radius: 12px;">
                            <?= $user['role'] === 'admin' ? 'Quản trị viên' : 'Sinh viên' ?>
                        </span>
                    </td>
                    <td>
                        <span class="table-status status-<?= $user['status'] === 'active' ? 'active' : 'inactive' ?>">
                            <?= $user['status'] === 'active' ? 'Hoạt động' : 'Không hoạt động' ?>
                        </span>
                    </td>
                    <td style="font-size: 12px; color: var(--color-text-muted);">
                        <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                    </td>
                    <td style="display: flex; gap: 4px;">
                        <button class="btn btn-sm btn-icon" onclick="editUser(<?= $user['id'] ?>)" title="Sửa">
                            <i class="ti ti-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon" onclick="resetUserPassword(<?= $user['id'] ?>)" title="Đặt lại mật khẩu">
                            <i class="ti ti-key"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-danger" onclick="deleteUser(<?= $user['id'] ?>)" title="Xóa">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: var(--color-text-muted);">
                        <i class="ti ti-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                        <p>Không tìm thấy tài khoản nào</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if (($pagination['total'] ?? 0) > ($pagination['per_page'] ?? 15)): ?>
    <div style="display: flex; justify-content: center; gap: 4px; margin-top: 2rem;">
        <?php if ($pagination['current_page'] > 1): ?>
            <button class="btn btn-sm" onclick="goToPage(<?= $pagination['current_page'] - 1 ?>)">← Trước</button>
        <?php endif; ?>

        <?php for ($i = 1; $i <= ($pagination['last_page'] ?? 1); $i++): ?>
            <button class="btn btn-sm <?= $i === ($pagination['current_page'] ?? 1) ? 'btn-primary' : '' ?>" 
                    onclick="goToPage(<?= $i ?>)">
                <?= $i ?>
            </button>
        <?php endfor; ?>

        <?php if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1)): ?>
            <button class="btn btn-sm" onclick="goToPage(<?= ($pagination['current_page'] ?? 1) + 1 ?>)">Sau →</button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Modal: Tạo/Sửa User -->
<div id="userModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h3 class="card-title" id="modalTitle">Tạo tài khoản mới</h3>
        </div>

        <form id="userForm" onsubmit="saveUser(event)">
            <div style="padding: 1.5rem;">
                <input type="hidden" id="userId">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?? '' ?>">

                <!-- User Fields -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Username</label>
                        <input type="text" name="username" class="form-input" id="usernameInput" placeholder="sv001" required>
                        <small style="color: var(--color-danger); display: none;" id="usernameError"></small>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Email</label>
                        <input type="email" name="email" class="form-input" id="emailInput" placeholder="user@ktx.edu.vn" required>
                        <small style="color: var(--color-danger); display: none;" id="emailError"></small>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Mật khẩu</label>
                        <input type="password" name="password" class="form-input" id="passwordInput" placeholder="••••••••" required>
                        <small style="color: var(--color-text-muted); font-size: 12px;">Tối thiểu 8 ký tự</small>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Xác nhận mật khẩu</label>
                        <input type="password" name="password_confirm" class="form-input" id="passwordConfirmInput" placeholder="••••••••" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Vai trò</label>
                        <select name="role" class="form-input" id="roleInput" onchange="updateFormFields()" required>
                            <option value="student">Sinh viên</option>
                            <option value="admin">Quản trị viên</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Trạng thái</label>
                        <select name="status" class="form-input" id="statusInput" required>
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                        </select>
                    </div>
                </div>

                <!-- Student Fields (shown only when role = student) -->
                <div id="studentFieldsGroup" style="display: none;">
                    <hr style="margin: 1rem 0; border: none; border-top: 0.5px solid var(--color-border);">
                    <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 1rem;">Thông tin sinh viên</h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 4px;">Mã sinh viên</label>
                            <input type="text" name="student_code" class="form-input" id="studentCodeInput" placeholder="123456">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 4px;">Họ và tên</label>
                            <input type="text" name="full_name" class="form-input" id="fullNameInput" placeholder="Nguyễn Văn A">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 4px;">Giới tính</label>
                            <select name="gender" class="form-input" id="genderInput">
                                <option value="">-- Chọn --</option>
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 4px;">Ngày sinh</label>
                            <input type="date" name="dob" class="form-input" id="dobInput">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 4px;">Khoa</label>
                            <input type="text" name="faculty" class="form-input" id="facultyInput" placeholder="CNTT">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 4px;">Điện thoại</label>
                            <input type="text" name="phone" class="form-input" id="phoneInput" placeholder="0123456789">
                        </div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">CMND/CCCD</label>
                        <input type="text" name="id_card" class="form-input" id="idCardInput" placeholder="0123456789">
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 1rem; border-top: 0.5px solid var(--color-border);">
                <button type="button" class="btn" onclick="closeUserModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Đặt lại mật khẩu -->
<div id="resetPasswordModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 90%; max-width: 400px;">
        <div class="card-header">
            <h3 class="card-title">Đặt lại mật khẩu</h3>
        </div>

        <form id="resetPasswordForm" onsubmit="submitResetPassword(event)">
            <div style="padding: 1.5rem;">
                <input type="hidden" id="resetUserId">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?? '' ?>">

                <div style="margin-bottom: 1rem; padding: 1rem; background: #FAEEDA; border-radius: 4px; font-size: 13px; color: #412402;">
                    <strong>⚠️ Lưu ý:</strong> Mật khẩu tạm thời sẽ được gửi cho sinh viên qua email.
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 4px;">Mật khẩu mới</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                    <small style="color: var(--color-text-muted); font-size: 12px;">Tối thiểu 8 ký tự</small>
                </div>

                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="send_email" checked>
                    <span>Gửi thông báo qua email</span>
                </label>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 1rem; border-top: 0.5px solid var(--color-border);">
                <button type="button" class="btn" onclick="closeResetPasswordModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Đặt lại</button>
            </div>
        </form>
    </div>
</div>

<script>
    let selectedUsers = new Set();

    /**
     * Mở modal tạo user mới
     */
    function openUserModal() {
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('modalTitle').textContent = 'Tạo tài khoản mới';
        document.getElementById('usernameInput').disabled = false;
        document.getElementById('passwordInput').disabled = false;
        updateFormFields();
        document.getElementById('userModal').style.display = 'flex';
    }

    /**
     * Đóng modal
     */
    function closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
    }

    /**
     * Cập nhật hiển thị fields sinh viên
     */
    function updateFormFields() {
        const role = document.getElementById('roleInput').value;
        const group = document.getElementById('studentFieldsGroup');
        group.style.display = role === 'student' ? 'block' : 'none';
    }

    /**
     * Lưu user (tạo hoặc cập nhật)
     */
    function saveUser(e) {
        e.preventDefault();

        const userId = document.getElementById('userId').value;
        const form = document.getElementById('userForm');
        const data = new FormData(form);
        const method = userId ? 'PUT' : 'POST';
        const url = userId ? `/api/users/${userId}` : '/api/users';

        fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.fromEntries(data))
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                showToast('success', json.message);
                closeUserModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                if (json.errors) {
                    Object.entries(json.errors).forEach(([field, msgs]) => {
                        const el = document.getElementById(field + 'Error');
                        if (el) {
                            el.textContent = msgs[0];
                            el.style.display = 'block';
                        }
                    });
                } else {
                    showToast('danger', json.message);
                }
            }
        })
        .catch(err => showToast('danger', 'Lỗi mạng: ' + err.message));
    }

    /**
     * Sửa user
     */
    function editUser(id) {
        fetch(`/api/users/${id}`)
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const user = json.data;
                    document.getElementById('userId').value = id;
                    document.getElementById('modalTitle').textContent = `Sửa tài khoản: ${user.username}`;
                    document.getElementById('usernameInput').value = user.username;
                    document.getElementById('usernameInput').disabled = true;
                    document.getElementById('emailInput').value = user.email;
                    document.getElementById('passwordInput').disabled = true;
                    document.getElementById('passwordConfirmInput').disabled = true;
                    document.getElementById('roleInput').value = user.role;
                    document.getElementById('statusInput').value = user.status;
                    updateFormFields();
                    document.getElementById('userModal').style.display = 'flex';
                }
            });
    }

    /**
     * Xóa user
     */
    function deleteUser(id) {
        if (!confirm('Bạn chắc chắn muốn xóa tài khoản này?')) return;

        fetch(`/api/users/${id}`, { method: 'DELETE' })
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    showToast('success', 'Đã xóa tài khoản');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('danger', json.message);
                }
            });
    }

    /**
     * Mở modal đặt lại mật khẩu
     */
    function resetUserPassword(id) {
        document.getElementById('resetUserId').value = id;
        document.getElementById('resetPasswordForm').reset();
        document.getElementById('resetPasswordModal').style.display = 'flex';
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').style.display = 'none';
    }

    function submitResetPassword(e) {
        e.preventDefault();

        const userId = document.getElementById('resetUserId').value;
        const form = document.getElementById('resetPasswordForm');
        const data = new FormData(form);

        fetch(`/api/users/${userId}/reset-password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.fromEntries(data))
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                showToast('success', json.message);
                closeResetPasswordModal();
            } else {
                showToast('danger', json.message);
            }
        });
    }

    /**
     * Bulk actions
     */
    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.user-checkbox').forEach(cb => {
            cb.checked = checked;
            const id = cb.closest('tr').dataset.userId;
            if (checked) {
                selectedUsers.add(parseInt(id));
            } else {
                selectedUsers.delete(parseInt(id));
            }
        });
        updateBulkActions();
    }

    function updateBulkActions() {
        selectedUsers.clear();
        document.querySelectorAll('.user-checkbox:checked').forEach(cb => {
            selectedUsers.add(parseInt(cb.closest('tr').dataset.userId));
        });

        const bulkDiv = document.getElementById('bulkActions');
        if (selectedUsers.size > 0) {
            bulkDiv.style.display = 'flex';
            document.getElementById('bulkCount').textContent = `Đã chọn ${selectedUsers.size} tài khoản`;
        } else {
            bulkDiv.style.display = 'none';
        }
    }

    function bulkSetStatus(status) {
        if (selectedUsers.size === 0) return;

        const action = status === 'active' ? 'kích hoạt' : 'vô hiệu hóa';
        if (!confirm(`Bạn chắc chắn muốn ${action} ${selectedUsers.size} tài khoản?`)) return;

        fetch('/api/users/bulk-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ids: Array.from(selectedUsers),
                status: status
            })
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                showToast('success', json.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('danger', json.message);
            }
        });
    }

    function clearSelection() {
        document.getElementById('selectAll').checked = false;
        selectedUsers.clear();
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
        updateBulkActions();
    }

    /**
     * Filter & Export
     */
    function filterUsers() {
        const search = document.getElementById('searchInput').value;
        const role = document.getElementById('roleFilter').value;
        const status = document.getElementById('statusFilter').value;
        window.location.search = `?q=${search}&role=${role}&status=${status}`;
    }

    function exportUsers() {
        window.location.href = '/api/users/export?format=csv';
    }

    function goToPage(page) {
        const search = document.getElementById('searchInput').value;
        const role = document.getElementById('roleFilter').value;
        const status = document.getElementById('statusFilter').value;
        window.location.search = `?page=${page}&q=${search}&role=${role}&status=${status}`;
    }
</script>
