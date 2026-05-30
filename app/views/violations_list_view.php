<?php
/**
 * app/views/admin/violations/index.php
 * ─────────────────────────────────────────────────────────────
 *  Danh sách vi phạm với AJAX CRUD
 *  Admin ghi nhận, sửa, xóa, xem khiếu nại
 * ─────────────────────────────────────────────────────────────
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý vi phạm KTX</h1>
        <p class="page-subtitle">Tổng số: <?= $pagination['total'] ?? 0 ?> vi phạm</p>
    </div>
    <button class="btn btn-primary" onclick="openViolationModal()">
        <i class="ti ti-plus"></i>
        <span>Ghi nhận vi phạm mới</span>
    </button>
</div>

<!-- Search & Filters -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; gap: 12px;">
        <div style="display: flex; gap: 12px; flex: 1;">
            <input 
                type="text" 
                id="searchInput"
                placeholder="Tìm kiếm (tên, mã SV)..." 
                style="flex: 1; padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;"
                onkeyup="filterViolations()"
            >
            <select 
                id="statusFilter"
                style="padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;"
                onchange="filterViolations()"
            >
                <option value="">Tất cả trạng thái</option>
                <option value="active">Đang xử lý</option>
                <option value="appealed">Khiếu nại</option>
                <option value="dismissed">Hủy bỏ</option>
            </select>
            <select 
                id="severityFilter"
                style="padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;"
                onchange="filterViolations()"
            >
                <option value="">Tất cả mức độ</option>
                <option value="1">Nhẹ</option>
                <option value="2">Trung bình</option>
                <option value="3">Nặng</option>
            </select>
        </div>
        <button class="btn btn-sm" onclick="exportViolations()">
            <i class="ti ti-download"></i>
            <span>Xuất Excel</span>
        </button>
    </div>
</div>

<!-- Violation Table -->
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Sinh viên</th>
                <th>Mã SV</th>
                <th>Loại vi phạm</th>
                <th>Điểm</th>
                <th>Mức độ</th>
                <th>Trạng thái</th>
                <th>Ngày ghi nhận</th>
                <th style="width: 150px;">Hành động</th>
            </tr>
        </thead>
        <tbody id="violationTableBody">
            <?php foreach (($violations ?? []) as $v): ?>
                <tr class="violation-row" data-violation-id="<?= $v['id'] ?>">
                    <td style="font-weight: 500;">
                        <?= htmlspecialchars($v['full_name']) ?>
                    </td>
                    <td style="font-size: 13px; color: var(--color-text-muted);">
                        <?= htmlspecialchars($v['student_code']) ?>
                    </td>
                    <td>
                        <span style="font-size: 13px;">
                            <?= htmlspecialchars($types[$v['violation_type']]['name'] ?? $v['violation_type']) ?>
                        </span>
                    </td>
                    <td style="font-weight: 600; font-size: 14px; color: var(--color-danger);">
                        <?= $v['penalty_points'] ?>
                    </td>
                    <td>
                        <span style="font-size: 12px; padding: 0.25rem 0.75rem; background: <?= 
                            $v['severity'] === 3 ? '#FCEBEB' :
                            ($v['severity'] === 2 ? '#FEF3E3' : '#E8F5E9')
                        ?>; color: <?=
                            $v['severity'] === 3 ? '#A32D2D' :
                            ($v['severity'] === 2 ? '#BA7517' : '#3B6D11')
                        ?>; border-radius: 12px;">
                            <?= $v['severity'] === 3 ? 'Nặng' : ($v['severity'] === 2 ? 'Trung bình' : 'Nhẹ') ?>
                        </span>
                    </td>
                    <td>
                        <span class="table-status status-<?= 
                            $v['status'] === 'active' ? 'active' :
                            ($v['status'] === 'appealed' ? 'pending' : 'inactive')
                        ?>">
                            <?= $v['status'] === 'active' ? 'Đang xử lý' : 
                                ($v['status'] === 'appealed' ? 'Khiếu nại' : 'Hủy bỏ') ?>
                        </span>
                    </td>
                    <td style="font-size: 12px; color: var(--color-text-muted);">
                        <?= date('d/m/Y', strtotime($v['created_at'])) ?>
                    </td>
                    <td style="display: flex; gap: 4px;">
                        <a href="/admin/violations/<?= $v['id'] ?>" class="btn btn-sm btn-icon" title="Chi tiết">
                            <i class="ti ti-eye"></i>
                        </a>
                        <button class="btn btn-sm btn-icon" onclick="editViolation(<?= $v['id'] ?>)" title="Sửa">
                            <i class="ti ti-edit"></i>
                        </button>
                        <?php if ($v['status'] === 'appealed'): ?>
                            <button class="btn btn-sm btn-icon" onclick="reviewAppeal(<?= $v['id'] ?>)" title="Xem khiếu nại" style="background: #E6F1FB;">
                                <i class="ti ti-message-circle"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-icon btn-danger" onclick="deleteViolation(<?= $v['id'] ?>)" title="Xóa">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($violations)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: var(--color-text-muted);">
                        <i class="ti ti-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                        <p>Không tìm thấy vi phạm nào</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if (($pagination['total'] ?? 0) > ($pagination['per_page'] ?? 15)): ?>
    <div style="display: flex; justify-content: center; gap: 4px; margin-top: 2rem;">
        <?php if (($pagination['current_page'] ?? 1) > 1): ?>
            <button class="btn btn-sm" onclick="goToPage(<?= ($pagination['current_page'] ?? 1) - 1 ?>)">← Trước</button>
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

<!-- Modal: Ghi nhận/Sửa vi phạm -->
<div id="violationModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h3 class="card-title" id="modalTitle">Ghi nhận vi phạm mới</h3>
        </div>

        <form id="violationForm" onsubmit="saveViolation(event)">
            <div style="padding: 1.5rem;">
                <input type="hidden" id="violationId">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?? '' ?>">

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 4px;">Sinh viên</label>
                    <select name="student_id" class="form-input" id="studentInput" required onchange="updateStudentInfo()">
                        <option value="">-- Chọn sinh viên --</option>
                        <?php
                        // Fetch all students
                        $students = $db->select("SELECT id, full_name, student_code FROM students ORDER BY full_name");
                        foreach ($students as $s):
                        ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= $s['student_code'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--color-text-muted); font-size: 12px;" id="studentPointsInfo"></small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Loại vi phạm</label>
                        <select name="violation_type" class="form-input" id="typeInput" required onchange="updateDefaultPoints()">
                            <option value="">-- Chọn loại --</option>
                            <option value="room_cleanliness">Phòng không sạch sẽ (1pt)</option>
                            <option value="noise">Gây tiếng ồn (2pts)</option>
                            <option value="guests">Khách không đăng ký (2pts)</option>
                            <option value="curfew">Vi phạm giờ về (3pts)</option>
                            <option value="unauthorized_item">Vật dụng cấm (3pts)</option>
                            <option value="smoking">Hút thuốc (3pts)</option>
                            <option value="alcohol">Uống rượu/bia (5pts)</option>
                            <option value="fighting">Đánh nhau/Cãi vã (7pts)</option>
                            <option value="damage">Phá hủy tài sản (8pts)</option>
                            <option value="theft">Trộm cắp (10pts)</option>
                            <option value="other">Khác (1pt)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Điểm (có thể sửa)</label>
                        <input type="number" name="override_points" class="form-input" id="pointsInput" min="1" max="10" placeholder="Tự động">
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 4px;">Mô tả</label>
                    <textarea name="description" class="form-input" id="descriptionInput" placeholder="Chi tiết về vi phạm..." required style="height: 80px;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Địa điểm</label>
                        <input type="text" name="location" class="form-input" id="locationInput" placeholder="Room 202, ...">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Người chứng kiến</label>
                        <input type="text" name="witnessed_by" class="form-input" id="witnessInput" placeholder="Họ tên nhân viên">
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 4px;">Bằng chứng</label>
                    <textarea name="evidence" class="form-input" id="evidenceInput" placeholder="Ảnh, video, tờ báo cáo..." style="height: 60px;"></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 1rem; border-top: 0.5px solid var(--color-border);">
                <button type="button" class="btn" onclick="closeViolationModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Review Appeal -->
<div id="appealModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 90%; max-width: 600px;">
        <div class="card-header">
            <h3 class="card-title">Xem xét khiếu nại</h3>
        </div>

        <div style="padding: 1.5rem;">
            <input type="hidden" id="appealViolationId">

            <div style="padding: 1rem; background: #E6F1FB; border-radius: 4px; margin-bottom: 1rem;">
                <div style="font-weight: 500; margin-bottom: 8px;">📋 Vi phạm</div>
                <div style="font-size: 14px;" id="appealViolationInfo"></div>
            </div>

            <div style="padding: 1rem; background: #FEF3E3; border-radius: 4px; margin-bottom: 1rem;">
                <div style="font-weight: 500; margin-bottom: 8px;">💬 Lý do khiếu nại</div>
                <div style="font-size: 14px;" id="appealReasonText"></div>
            </div>

            <div style="display: flex; gap: 8px;">
                <button class="btn btn-sm btn-success" onclick="acceptAppeal()">
                    <i class="ti ti-check"></i>
                    <span>Chấp nhận khiếu nại</span>
                </button>
                <button class="btn btn-sm btn-danger" onclick="rejectAppeal()">
                    <i class="ti ti-x"></i>
                    <span>Từ chối khiếu nại</span>
                </button>
            </div>
        </div>

        <div style="padding: 1rem; border-top: 0.5px solid var(--color-border);">
            <button class="btn" onclick="closeAppealModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
    const VIOLATION_POINTS = {
        'room_cleanliness': 1,
        'noise': 2,
        'guests': 2,
        'curfew': 3,
        'unauthorized_item': 3,
        'smoking': 3,
        'alcohol': 5,
        'fighting': 7,
        'damage': 8,
        'theft': 10,
        'other': 1,
    };

    /**
     * Mở modal ghi nhận vi phạm mới
     */
    function openViolationModal() {
        document.getElementById('violationForm').reset();
        document.getElementById('violationId').value = '';
        document.getElementById('modalTitle').textContent = 'Ghi nhận vi phạm mới';
        document.getElementById('pointsInput').placeholder = 'Tự động';
        document.getElementById('violationModal').style.display = 'flex';
    }

    function closeViolationModal() {
        document.getElementById('violationModal').style.display = 'none';
    }

    /**
     * Cập nhật điểm mặc định khi chọn loại vi phạm
     */
    function updateDefaultPoints() {
        const type = document.getElementById('typeInput').value;
        if (type && VIOLATION_POINTS[type]) {
            document.getElementById('pointsInput').placeholder = VIOLATION_POINTS[type] + ' (mặc định)';
        }
    }

    /**
     * Cập nhật thông tin sinh viên (tổng điểm)
     */
    function updateStudentInfo() {
        const studentId = document.getElementById('studentInput').value;
        if (!studentId) {
            document.getElementById('studentPointsInfo').textContent = '';
            return;
        }

        fetch(`/api/student/violations?student_id=${studentId}`)
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const info = json.data;
                    document.getElementById('studentPointsInfo').textContent = 
                        `Điểm hiện tại: ${info.active_points}/${info.threshold}`;
                }
            });
    }

    /**
     * Lưu vi phạm (tạo hoặc sửa)
     */
    function saveViolation(e) {
        e.preventDefault();

        const violationId = document.getElementById('violationId').value;
        const form = document.getElementById('violationForm');
        const data = new FormData(form);
        const method = violationId ? 'PUT' : 'POST';
        const url = violationId ? `/api/violations/${violationId}` : '/api/violations';

        fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.fromEntries(data))
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                showToast('success', json.message);
                closeViolationModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('danger', json.message);
            }
        })
        .catch(err => showToast('danger', 'Lỗi mạng: ' + err.message));
    }

    /**
     * Sửa vi phạm
     */
    function editViolation(id) {
        fetch(`/api/violations/${id}`)
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const v = json.data;
                    document.getElementById('violationId').value = id;
                    document.getElementById('modalTitle').textContent = 'Sửa vi phạm';
                    document.getElementById('studentInput').value = v.student_id;
                    document.getElementById('typeInput').value = v.violation_type;
                    document.getElementById('pointsInput').value = v.penalty_points;
                    document.getElementById('descriptionInput').value = v.description;
                    document.getElementById('locationInput').value = v.location;
                    document.getElementById('witnessInput').value = v.witnessed_by;
                    document.getElementById('evidenceInput').value = v.evidence;
                    document.getElementById('violationModal').style.display = 'flex';
                }
            });
    }

    /**
     * Xóa vi phạm
     */
    function deleteViolation(id) {
        if (!confirm('Bạn chắc chắn muốn xóa vi phạm này?')) return;

        fetch(`/api/violations/${id}`, { method: 'DELETE' })
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    showToast('success', 'Đã xóa vi phạm');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('danger', json.message);
                }
            });
    }

    /**
     * Xem xét khiếu nại
     */
    function reviewAppeal(id) {
        fetch(`/api/violations/${id}`)
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const v = json.data;
                    document.getElementById('appealViolationId').value = id;
                    document.getElementById('appealViolationInfo').textContent = 
                        `${v.violation_type} - ${v.penalty_points} điểm - ${v.description}`;
                    document.getElementById('appealReasonText').textContent = 
                        v.appeal_reason || 'Không có lý do';
                    document.getElementById('appealModal').style.display = 'flex';
                }
            });
    }

    function closeAppealModal() {
        document.getElementById('appealModal').style.display = 'none';
    }

    function acceptAppeal() {
        const id = document.getElementById('appealViolationId').value;
        fetch(`/api/violations/${id}/accept-appeal`, { method: 'POST' })
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    showToast('success', 'Chấp nhận khiếu nại');
                    closeAppealModal();
                    setTimeout(() => location.reload(), 1000);
                }
            });
    }

    function rejectAppeal() {
        const reason = prompt('Lý do từ chối:');
        if (!reason) return;

        const id = document.getElementById('appealViolationId').value;
        fetch(`/api/violations/${id}/dismiss-appeal`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reason })
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                showToast('success', 'Từ chối khiếu nại');
                closeAppealModal();
                setTimeout(() => location.reload(), 1000);
            }
        });
    }

    /**
     * Filter & Export
     */
    function filterViolations() {
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const severity = document.getElementById('severityFilter').value;
        window.location.search = `?q=${search}&status=${status}&severity=${severity}`;
    }

    function exportViolations() {
        window.location.href = '/api/violations/export?format=csv';
    }

    function goToPage(page) {
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const severity = document.getElementById('severityFilter').value;
        window.location.search = `?page=${page}&q=${search}&status=${status}&severity=${severity}`;
    }
</script>
