<?php
/**
 * app/views/admin/rooms/index.php
 * ─────────────────────────────────────────────────────────────
 *  Danh sách phòng — Admin panel
 *  Variables:
 *    $title        string
 *    $rooms[]      array (room_number, floor, room_type, capacity,
 *                         current_occupants, price_per_month,
 *                         has_ac, status, building_name, id)
 *    $buildings[]  array (id, name)  — for filter dropdown
 *    $pagination[] array (data/total/current_page/last_page/per_page/from/to)
 * ─────────────────────────────────────────────────────────────
 */

$currentPage    = (int)($pagination['current_page'] ?? 1);
$lastPage       = (int)($pagination['last_page']    ?? 1);
$total          = (int)($pagination['total']        ?? 0);
$from           = (int)($pagination['from']         ?? 0);
$to             = (int)($pagination['to']           ?? 0);

$currentQ       = htmlspecialchars($_GET['q']        ?? '');
$currentBuilding= htmlspecialchars($_GET['building'] ?? '');
$currentStatus  = htmlspecialchars($_GET['status']   ?? '');

// Status config
$statusConfig = [
    'available'   => ['badge-success', '✅ Còn trống'],
    'full'        => ['badge-danger',  '🔴 Đầy'],
    'maintenance' => ['badge-warning', '🔧 Bảo trì'],
    'inactive'    => ['badge-neutral', '⛔ Không hoạt động'],
];

// Room type labels
$typeLabels = [
    'single'  => '👤 Đơn',
    'double'  => '👥 Đôi',
    'triple'  => '👥 Ba người',
    'quad'    => '👥 Bốn người',
    'dormitory'=> '🏠 Tập thể',
];
?>

<!-- ── Page Header ──────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">🚪 Quản lý phòng</h1>
        <p class="page-subtitle">Tổng cộng <?= number_format($total) ?> phòng trong hệ thống</p>
    </div>
    <div class="page-actions">
        <a href="/testfinal/public/admin/rooms/map" class="btn btn-outline btn-sm">🗺️ Sơ đồ phòng</a>
        <button class="btn btn-outline btn-sm" onclick="exportRooms()">📥 Xuất Excel</button>
        <button class="btn btn-primary" data-modal-open="modalAddRoom">➕ Thêm phòng</button>
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

<!-- ── Quick Stats Strip ────────────────────────────────────── -->
<?php
    $availableCount   = 0; $fullCount = 0; $maintenanceCount = 0;
    foreach (($rooms ?? []) as $rm) {
        $s = $rm['status'] ?? '';
        if ($s === 'available')   $availableCount++;
        elseif ($s === 'full')    $fullCount++;
        elseif ($s === 'maintenance') $maintenanceCount++;
    }
?>
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <div style="flex:1;min-width:140px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 16px;display:flex;align-items:center;gap:10px;border-left:3px solid #10b981">
        <span style="font-size:22px">✅</span>
        <div><div style="font-size:20px;font-weight:800;color:#10b981"><?= number_format($availableCount) ?></div><div style="font-size:11px;color:var(--txt-muted);text-transform:uppercase">Còn trống</div></div>
    </div>
    <div style="flex:1;min-width:140px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 16px;display:flex;align-items:center;gap:10px;border-left:3px solid #ef4444">
        <span style="font-size:22px">🔴</span>
        <div><div style="font-size:20px;font-weight:800;color:#ef4444"><?= number_format($fullCount) ?></div><div style="font-size:11px;color:var(--txt-muted);text-transform:uppercase">Đầy</div></div>
    </div>
    <div style="flex:1;min-width:140px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 16px;display:flex;align-items:center;gap:10px;border-left:3px solid #f59e0b">
        <span style="font-size:22px">🔧</span>
        <div><div style="font-size:20px;font-weight:800;color:#f59e0b"><?= number_format($maintenanceCount) ?></div><div style="font-size:11px;color:var(--txt-muted);text-transform:uppercase">Bảo trì</div></div>
    </div>
    <div style="flex:1;min-width:140px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:12px 16px;display:flex;align-items:center;gap:10px;border-left:3px solid #6366f1">
        <span style="font-size:22px">🚪</span>
        <div><div style="font-size:20px;font-weight:800;color:#6366f1"><?= number_format($total) ?></div><div style="font-size:11px;color:var(--txt-muted);text-transform:uppercase">Tổng phòng</div></div>
    </div>
</div>

<!-- ── Table Card ───────────────────────────────────────────── -->
<div class="card">

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-search">
            <span class="search-icon">🔍</span>
            <input type="text"
                   id="searchInput"
                   class="form-control"
                   placeholder="Tìm số phòng, tòa nhà..."
                   value="<?= $currentQ ?>"
                   onkeydown="if(event.key==='Enter') applyFilters()">
        </div>

        <select id="buildingFilter" class="form-control" style="width:auto" onchange="applyFilters()">
            <option value="">Tất cả tòa nhà</option>
            <?php foreach (($buildings ?? []) as $b): ?>
                <option value="<?= (int)$b['id'] ?>"
                        <?= (string)($b['id'] ?? '') === $currentBuilding ? 'selected' : '' ?>>
                    🏢 <?= htmlspecialchars($b['name'] ?? '') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="statusFilter" class="form-control" style="width:auto" onchange="applyFilters()">
            <option value="">Tất cả trạng thái</option>
            <option value="available"   <?= $currentStatus === 'available'   ? 'selected' : '' ?>>✅ Còn trống</option>
            <option value="full"        <?= $currentStatus === 'full'        ? 'selected' : '' ?>>🔴 Đầy</option>
            <option value="maintenance" <?= $currentStatus === 'maintenance' ? 'selected' : '' ?>>🔧 Bảo trì</option>
            <option value="inactive"    <?= $currentStatus === 'inactive'    ? 'selected' : '' ?>>⛔ Không hoạt động</option>
        </select>

        <?php if ($currentQ || $currentBuilding || $currentStatus): ?>
            <a href="/testfinal/public/admin/rooms" class="btn btn-ghost btn-sm">✕ Xóa bộ lọc</a>
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
                    <th>Phòng</th>
                    <th>Tòa nhà</th>
                    <th>Loại phòng</th>
                    <th>Sức chứa</th>
                    <th>Giá/tháng</th>
                    <th>Trạng thái</th>
                    <th style="width:50px;text-align:center">AC</th>
                    <th style="width:130px;text-align:center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rooms)): ?>
                    <?php foreach ($rooms as $i => $room): ?>
                        <?php
                            $rid         = (int)($room['id'] ?? 0);
                            $capacity    = (int)($room['capacity']          ?? 0);
                            $occupants   = (int)($room['current_occupants'] ?? 0);
                            $pct         = $capacity > 0 ? min(100, round(($occupants / $capacity) * 100)) : 0;
                            $barClass    = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success');
                            $status      = $room['status'] ?? 'available';
                            [$badgeClass, $statusLabel] = $statusConfig[$status] ?? ['badge-neutral', $status];
                            $typeKey     = $room['room_type'] ?? '';
                            $typeLabel   = $typeLabels[$typeKey] ?? ucfirst($typeKey) ?: '—';
                            $price       = (float)($room['price_per_month'] ?? 0);
                            $hasAc       = !empty($room['has_ac']) && $room['has_ac'] != '0';
                            $rowNum      = $from + $i;
                        ?>
                        <tr>
                            <td style="color:var(--txt-muted);font-size:12px"><?= $rowNum ?></td>

                            <!-- Phòng -->
                            <td>
                                <div style="font-size:16px;font-weight:800;letter-spacing:-.3px;color:var(--txt-primary)">
                                    <?= htmlspecialchars($room['room_number'] ?? '—') ?>
                                </div>
                                <?php if (!empty($room['floor'])): ?>
                                    <div class="sub">Tầng <?= htmlspecialchars($room['floor']) ?></div>
                                <?php endif; ?>
                            </td>

                            <!-- Tòa nhà -->
                            <td>
                                <div style="display:flex;align-items:center;gap:6px">
                                    <span style="font-size:16px">🏢</span>
                                    <span style="font-weight:500"><?= htmlspecialchars($room['building_name'] ?? '—') ?></span>
                                </div>
                            </td>

                            <!-- Loại -->
                            <td>
                                <span class="badge badge-info" style="font-size:11.5px"><?= $typeLabel ?></span>
                            </td>

                            <!-- Sức chứa + progress -->
                            <td>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <div>
                                        <span style="font-weight:700;color:var(--txt-primary)"><?= $occupants ?></span>
                                        <span style="color:var(--txt-muted)">/<?= $capacity ?></span>
                                    </div>
                                    <div>
                                        <div class="progress" style="width:80px">
                                            <div class="progress-bar <?= $barClass ?>"
                                                 style="width:<?= $pct ?>%"></div>
                                        </div>
                                        <div style="font-size:10px;color:var(--txt-muted);margin-top:2px;text-align:right"><?= $pct ?>%</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Giá -->
                            <td>
                                <div style="font-weight:700;color:var(--txt-primary)">
                                    <?= number_format($price, 0, ',', '.') ?>đ
                                </div>
                                <div class="sub">/ tháng</div>
                            </td>

                            <!-- Trạng thái -->
                            <td>
                                <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </td>

                            <!-- AC -->
                            <td style="text-align:center">
                                <?php if ($hasAc): ?>
                                    <span title="Có điều hòa" style="font-size:18px">❄️</span>
                                <?php else: ?>
                                    <span title="Không có điều hòa" style="font-size:16px;opacity:.3">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td>
                                <div style="display:flex;gap:4px;justify-content:center">
                                    <a href="/testfinal/public/admin/rooms/<?= $rid ?>"
                                       class="btn btn-ghost btn-sm" title="Xem chi tiết">
                                        👁️
                                    </a>
                                    <button class="btn btn-ghost btn-sm"
                                            title="Sửa"
                                            onclick="openEditRoomModal(<?= $rid ?>)">
                                        ✏️
                                    </button>
                                    <button class="btn btn-danger-outline btn-sm"
                                            title="Xóa"
                                            onclick="deleteRoom(<?= $rid ?>, '<?= htmlspecialchars(addslashes($room['room_number'] ?? '')) ?>')">
                                        🗑️
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-icon">🚪</div>
                                <div class="empty-title">Không tìm thấy phòng</div>
                                <div class="empty-msg">
                                    <?php if ($currentQ || $currentBuilding || $currentStatus): ?>
                                        Không có phòng nào phù hợp với bộ lọc hiện tại.
                                        <a href="/testfinal/public/admin/rooms">Xóa bộ lọc</a>
                                    <?php else: ?>
                                        Chưa có phòng nào trong hệ thống.
                                        <button class="btn btn-primary btn-sm" style="margin-top:12px"
                                                data-modal-open="modalAddRoom">Thêm phòng đầu tiên</button>
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
                Trang <?= $currentPage ?> / <?= $lastPage ?> &nbsp;·&nbsp; <?= number_format($total) ?> phòng
            </span>
            <div class="pagination" style="margin-left:auto">
                <!-- Prev -->
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>&q=<?= $currentQ ?>&building=<?= $currentBuilding ?>&status=<?= $currentStatus ?>"
                       class="page-link">‹</a>
                <?php else: ?>
                    <span class="page-link disabled">‹</span>
                <?php endif; ?>

                <?php
                    $startP = max(1, $currentPage - 2);
                    $endP   = min($lastPage, $currentPage + 2);
                ?>
                <?php if ($startP > 1): ?>
                    <a href="?page=1&q=<?= $currentQ ?>&building=<?= $currentBuilding ?>&status=<?= $currentStatus ?>" class="page-link">1</a>
                    <?php if ($startP > 2): ?><span class="page-link disabled">…</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $startP; $p <= $endP; $p++): ?>
                    <a href="?page=<?= $p ?>&q=<?= $currentQ ?>&building=<?= $currentBuilding ?>&status=<?= $currentStatus ?>"
                       class="page-link <?= $p === $currentPage ? 'active' : '' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($endP < $lastPage): ?>
                    <?php if ($endP < $lastPage - 1): ?><span class="page-link disabled">…</span><?php endif; ?>
                    <a href="?page=<?= $lastPage ?>&q=<?= $currentQ ?>&building=<?= $currentBuilding ?>&status=<?= $currentStatus ?>"
                       class="page-link"><?= $lastPage ?></a>
                <?php endif; ?>

                <!-- Next -->
                <?php if ($currentPage < $lastPage): ?>
                    <a href="?page=<?= $currentPage + 1 ?>&q=<?= $currentQ ?>&building=<?= $currentBuilding ?>&status=<?= $currentStatus ?>"
                       class="page-link">›</a>
                <?php else: ?>
                    <span class="page-link disabled">›</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div><!-- /.card -->


<!-- ══════════════════════════════════════════════════════════ -->
<!--  MODAL: Thêm phòng                                        -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalAddRoom">
    <div class="modal" style="max-width:580px">
        <div class="modal-header">
            <div class="modal-title">➕ Thêm phòng mới</div>
            <button class="modal-close" data-modal-close="modalAddRoom">×</button>
        </div>
        <form id="formAddRoom" onsubmit="submitAddRoom(event)">
            <div class="modal-body">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Số phòng <span class="req">*</span></label>
                        <input type="text" name="room_number" class="form-control"
                               placeholder="A1-101" required>
                        <div class="form-error d-none" id="add_room_number_err"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tầng</label>
                        <input type="number" name="floor" class="form-control"
                               placeholder="1" min="1" max="50">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tòa nhà <span class="req">*</span></label>
                        <select name="building_id" class="form-control" required>
                            <option value="">-- Chọn tòa nhà --</option>
                            <?php foreach (($buildings ?? []) as $b): ?>
                                <option value="<?= (int)$b['id'] ?>">
                                    <?= htmlspecialchars($b['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-error d-none" id="add_building_err"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Loại phòng <span class="req">*</span></label>
                        <select name="room_type" class="form-control" required>
                            <option value="">-- Chọn loại --</option>
                            <option value="single">👤 Đơn</option>
                            <option value="double">👥 Đôi</option>
                            <option value="triple">👥 Ba người</option>
                            <option value="quad">👥 Bốn người</option>
                            <option value="dormitory">🏠 Tập thể</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Sức chứa tối đa <span class="req">*</span></label>
                        <input type="number" name="capacity" class="form-control"
                               placeholder="4" min="1" max="20" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Giá / tháng (VNĐ) <span class="req">*</span></label>
                        <input type="number" name="price_per_month" class="form-control"
                               placeholder="1500000" min="0" step="50000" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-control">
                            <option value="available">✅ Còn trống</option>
                            <option value="maintenance">🔧 Bảo trì</option>
                            <option value="inactive">⛔ Không hoạt động</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px">
                        <label class="form-check">
                            <input type="checkbox" name="has_ac" value="1">
                            <span style="font-size:13.5px">❄️ Có điều hòa nhiệt độ</span>
                        </label>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">Ghi chú</label>
                    <textarea name="description" class="form-control"
                              placeholder="Mô tả thêm về phòng (không bắt buộc)..."
                              rows="3"></textarea>
                </div>

            </div><!-- /.modal-body -->
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modalAddRoom">Hủy</button>
                <button type="submit" class="btn btn-primary" id="btnAddRoom">
                    <span>➕ Thêm phòng</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!--  MODAL: Sửa phòng                                         -->
<!-- ══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalEditRoom">
    <div class="modal" style="max-width:580px">
        <div class="modal-header">
            <div class="modal-title">✏️ Sửa thông tin phòng</div>
            <button class="modal-close" data-modal-close="modalEditRoom">×</button>
        </div>
        <form id="formEditRoom" onsubmit="submitEditRoom(event)">
            <div class="modal-body">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
                <input type="hidden" name="room_id"    id="edit_room_id">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Số phòng</label>
                        <input type="text" id="edit_room_number" class="form-control"
                               disabled style="background:#f8fafc;cursor:not-allowed">
                        <div class="form-hint">Số phòng không thể thay đổi.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tầng</label>
                        <input type="number" name="floor" id="edit_floor"
                               class="form-control" min="1" max="50">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tòa nhà <span class="req">*</span></label>
                        <select name="building_id" id="edit_building_id" class="form-control" required>
                            <option value="">-- Chọn tòa nhà --</option>
                            <?php foreach (($buildings ?? []) as $b): ?>
                                <option value="<?= (int)$b['id'] ?>">
                                    <?= htmlspecialchars($b['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Loại phòng</label>
                        <select name="room_type" id="edit_room_type" class="form-control">
                            <option value="single">👤 Đơn</option>
                            <option value="double">👥 Đôi</option>
                            <option value="triple">👥 Ba người</option>
                            <option value="quad">👥 Bốn người</option>
                            <option value="dormitory">🏠 Tập thể</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Sức chứa tối đa</label>
                        <input type="number" name="capacity" id="edit_capacity"
                               class="form-control" min="1" max="20">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Giá / tháng (VNĐ)</label>
                        <input type="number" name="price_per_month" id="edit_price"
                               class="form-control" min="0" step="50000">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="available">✅ Còn trống</option>
                            <option value="full">🔴 Đầy</option>
                            <option value="maintenance">🔧 Bảo trì</option>
                            <option value="inactive">⛔ Không hoạt động</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px">
                        <label class="form-check">
                            <input type="checkbox" name="has_ac" id="edit_has_ac" value="1">
                            <span style="font-size:13.5px">❄️ Có điều hòa nhiệt độ</span>
                        </label>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">Ghi chú</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"
                              placeholder="Mô tả thêm..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close="modalEditRoom">Hủy</button>
                <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>


<script>
/* ── Modal helpers ─────────────────────────────────────────── */
function openModal(id)  { const el = document.getElementById(id); if (el) el.classList.add('open'); }
function closeModal(id) { const el = document.getElementById(id); if (el) el.classList.remove('open'); }

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(btn.getAttribute('data-modal-open')); });
    });
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal(btn.getAttribute('data-modal-close')); });
    });
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });
});

/* ── Filter helpers ────────────────────────────────────────── */
function applyFilters() {
    const q        = document.getElementById('searchInput').value;
    const building = document.getElementById('buildingFilter').value;
    const status   = document.getElementById('statusFilter').value;
    window.location.href = '?q=' + encodeURIComponent(q)
                         + '&building=' + encodeURIComponent(building)
                         + '&status='   + encodeURIComponent(status)
                         + '&page=1';
}

/* ── Add Room ───────────────────────────────────────────────── */
function submitAddRoom(e) {
    e.preventDefault();
    const form = document.getElementById('formAddRoom');
    const btn  = document.getElementById('btnAddRoom');
    const data = new FormData(form);

    document.querySelectorAll('#formAddRoom .form-error').forEach(el => {
        el.textContent = ''; el.classList.add('d-none');
    });

    btn.disabled = true;
    btn.innerHTML = '<span class="loading"></span> Đang thêm...';

    // Serialize checkboxes correctly
    const payload = Object.fromEntries(data);
    payload.has_ac = form.querySelector('[name="has_ac"]')?.checked ? 1 : 0;

    fetch('/testfinal/public/api/rooms', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': payload._csrf_token },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            closeModal('modalAddRoom');
            showKtxToast('success', '✅ Thêm phòng thành công!');
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
        btn.innerHTML = '<span>➕ Thêm phòng</span>';
    });
}

/* ── Open Edit Room Modal ───────────────────────────────────── */
function openEditRoomModal(id) {
    fetch('/testfinal/public/api/rooms/' + id)
    .then(r => r.json())
    .then(json => {
        if (!json.success) { showKtxToast('error', 'Không tải được thông tin phòng.'); return; }
        const rm = json.data;
        document.getElementById('edit_room_id').value      = rm.id;
        document.getElementById('edit_room_number').value  = rm.room_number;
        document.getElementById('edit_floor').value        = rm.floor        || '';
        document.getElementById('edit_building_id').value  = rm.building_id  || '';
        document.getElementById('edit_room_type').value    = rm.room_type    || 'single';
        document.getElementById('edit_capacity').value     = rm.capacity     || '';
        document.getElementById('edit_price').value        = rm.price_per_month || '';
        document.getElementById('edit_status').value       = rm.status       || 'available';
        document.getElementById('edit_has_ac').checked     = !!rm.has_ac;
        document.getElementById('edit_description').value  = rm.description  || '';
        openModal('modalEditRoom');
    })
    .catch(() => showKtxToast('error', 'Lỗi kết nối.'));
}

/* ── Submit Edit Room ───────────────────────────────────────── */
function submitEditRoom(e) {
    e.preventDefault();
    const form   = document.getElementById('formEditRoom');
    const data   = new FormData(form);
    const roomId = document.getElementById('edit_room_id').value;

    const payload = Object.fromEntries(data);
    payload.has_ac = form.querySelector('[name="has_ac"]')?.checked ? 1 : 0;

    fetch('/testfinal/public/api/rooms/' + roomId, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': payload._csrf_token },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            closeModal('modalEditRoom');
            showKtxToast('success', '✅ Cập nhật phòng thành công!');
            setTimeout(() => location.reload(), 1200);
        } else {
            showKtxToast('error', json.message || 'Cập nhật thất bại.');
        }
    })
    .catch(err => showKtxToast('error', 'Lỗi kết nối: ' + err.message));
}

/* ── Delete Room ────────────────────────────────────────────── */
function deleteRoom(id, roomNumber) {
    if (!confirm('Bạn có chắc muốn xóa phòng "' + roomNumber + '"?\n\nLưu ý: Không thể xóa phòng đang có sinh viên!')) return;

    fetch('/testfinal/public/api/rooms/' + id, { method: 'DELETE' })
    .then(r => r.json())
    .then(json => {
        if (json.success) {
            showKtxToast('success', '🗑️ Đã xóa phòng ' + roomNumber);
            setTimeout(() => location.reload(), 1200);
        } else {
            showKtxToast('error', json.message || 'Xóa thất bại. Phòng có thể đang được sử dụng.');
        }
    })
    .catch(err => showKtxToast('error', 'Lỗi kết nối: ' + err.message));
}

/* ── Export ─────────────────────────────────────────────────── */
function exportRooms() {
    const q        = document.getElementById('searchInput')?.value ?? '';
    const building = document.getElementById('buildingFilter')?.value ?? '';
    const status   = document.getElementById('statusFilter')?.value ?? '';
    window.location.href = '/testfinal/public/api/rooms/export'
                         + '?q=' + encodeURIComponent(q)
                         + '&building=' + encodeURIComponent(building)
                         + '&status='   + encodeURIComponent(status);
}

/* ── Toast helper ───────────────────────────────────────────── */
function showKtxToast(type, msg) {
    if (window.ktx && window.ktx.toast) { window.ktx.toast(type, msg); return; }
    const toast = document.createElement('div');
    toast.style.cssText = [
        'position:fixed;bottom:24px;right:24px;z-index:9999',
        'padding:12px 20px;border-radius:8px;font-size:13.5px;font-weight:600',
        'color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.25)',
        'max-width:360px',
        type === 'success' ? 'background:#10b981' : 'background:#ef4444'
    ].join(';');
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}
</script>
