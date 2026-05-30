<?php
/**
 * app/views/admin/rooms/index.php
 * Room listing with AJAX add/edit/delete
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quản lý phòng KTX</h1>
        <p class="page-subtitle">Tổng số: <?= count($rooms ?? []) ?> phòng</p>
    </div>
    <button class="btn btn-primary" onclick="openRoomModal()">
        <i class="ti ti-plus"></i>
        <span>Thêm phòng mới</span>
    </button>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; gap: 12px;">
        <input type="text" placeholder="Tìm kiếm phòng..." style="flex: 1;" id="searchInput" onkeyup="filterRooms()">
        <select style="padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;" id="buildingFilter" onchange="filterRooms()">
            <option value="">Tất cả tòa nhà</option>
            <?php foreach ($buildings ?? [] as $building): ?>
                <option value="<?= $building['id'] ?>"><?= htmlspecialchars($building['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select style="padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;" id="statusFilter" onchange="filterRooms()">
            <option value="">Tất cả trạng thái</option>
            <option value="available">Còn trống</option>
            <option value="full">Đầy</option>
            <option value="maintenance">Bảo trì</option>
        </select>
    </div>
</div>

<!-- Room Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
    <?php foreach (($rooms ?? []) as $room): ?>
        <div class="card" style="cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='var(--color-border)'">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div>
                    <div style="font-size: 16px; font-weight: 600;"><?= htmlspecialchars($room['room_number']) ?></div>
                    <div style="font-size: 12px; color: var(--color-text-muted);"><?= htmlspecialchars($room['building_name']) ?></div>
                </div>
                <span class="table-status status-<?= $room['status'] === 'available' ? 'active' : ($room['status'] === 'full' ? 'error' : 'inactive') ?>">
                    <?= ucfirst($room['status']) ?>
                </span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 0.5px solid var(--color-border);">
                <div>
                    <div style="font-size: 12px; color: var(--color-text-muted);">Giường</div>
                    <div style="font-size: 16px; font-weight: 600;"><?= $room['current_occupants'] ?>/<?= $room['capacity'] ?></div>
                </div>
                <div>
                    <div style="font-size: 12px; color: var(--color-text-muted);">Giá/tháng</div>
                    <div style="font-size: 14px; font-weight: 600;"><?= number_format($room['price_per_month'], 0) ?> VND</div>
                </div>
            </div>

            <div style="display: flex; gap: 8px;">
                <button class="btn btn-sm" style="flex: 1;" onclick="editRoom(<?= $room['id'] ?>)">
                    <i class="ti ti-edit"></i>
                    <span>Sửa</span>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteRoom(<?= $room['id'] ?>)">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add/Edit Room Modal -->
<div id="roomModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 90%; max-width: 500px;">
        <div class="card-header">
            <h3 class="card-title" id="modalTitle">Thêm phòng mới</h3>
        </div>
        
        <form id="roomForm" onsubmit="saveRoom(event)">
            <div style="padding: 1.5rem;">
                <input type="hidden" id="roomId">
                <input type="hidden" name="_csrf_token" value="<?= $csrfToken ?? '' ?>">

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 4px;">Tòa nhà</label>
                    <select name="building_id" required style="width: 100%; padding: 0.5rem; border: 0.5px solid var(--color-border); border-radius: 4px;">
                        <option>-- Chọn tòa nhà --</option>
                        <?php foreach ($buildings ?? [] as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Số phòng</label>
                        <input type="text" name="room_number" required style="width: 100%; padding: 0.5rem; border: 0.5px solid var(--color-border); border-radius: 4px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Tầng</label>
                        <input type="number" name="floor" min="1" required style="width: 100%; padding: 0.5rem; border: 0.5px solid var(--color-border); border-radius: 4px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Loại phòng</label>
                        <select name="room_type" required style="width: 100%; padding: 0.5rem; border: 0.5px solid var(--color-border); border-radius: 4px;">
                            <option value="standard">Chuẩn</option>
                            <option value="deluxe">Cao cấp</option>
                            <option value="ac_standard">Chuẩn + AC</option>
                            <option value="ac_deluxe">Cao cấp + AC</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 4px;">Số giường</label>
                        <input type="number" name="capacity" min="1" max="10" required style="width: 100%; padding: 0.5rem; border: 0.5px solid var(--color-border); border-radius: 4px;">
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 500; margin-bottom: 4px;">Giá/tháng (VND)</label>
                    <input type="number" name="price_per_month" min="100000" step="100000" required style="width: 100%; padding: 0.5rem; border: 0.5px solid var(--color-border); border-radius: 4px;">
                </div>

                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="has_ac">
                    <span>Có điều hòa</span>
                </label>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 1rem; border-top: 0.5px solid var(--color-border);">
                <button type="button" class="btn" onclick="closeRoomModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRoomModal() {
        document.getElementById('roomModal').style.display = 'flex';
        document.getElementById('roomForm').reset();
        document.getElementById('roomId').value = '';
        document.getElementById('modalTitle').textContent = 'Thêm phòng mới';
    }

    function closeRoomModal() {
        document.getElementById('roomModal').style.display = 'none';
    }

    function editRoom(id) {
        // Fetch room data and populate form
        fetch(`/api/rooms/${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const form = document.getElementById('roomForm');
                    form.building_id.value = data.data.building_id;
                    form.room_number.value = data.data.room_number;
                    form.floor.value = data.data.floor;
                    form.room_type.value = data.data.room_type;
                    form.capacity.value = data.data.capacity;
                    form.price_per_month.value = data.data.price_per_month;
                    form.has_ac.checked = data.data.has_ac;
                    document.getElementById('roomId').value = id;
                    document.getElementById('modalTitle').textContent = `Sửa phòng ${data.data.room_number}`;
                    openRoomModal();
                }
            });
    }

    function saveRoom(e) {
        e.preventDefault();
        const roomId = document.getElementById('roomId').value;
        const form = document.getElementById('roomForm');
        const data = new FormData(form);
        const method = roomId ? 'PUT' : 'POST';
        const url = roomId ? `/admin/rooms/${roomId}` : '/admin/rooms';

        fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(Object.fromEntries(data))
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                showToast('success', json.message);
                closeRoomModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('danger', json.message);
            }
        });
    }

    function deleteRoom(id) {
        if (!confirm('Bạn chắc chắn muốn xóa phòng này?')) return;
        fetch(`/admin/rooms/${id}`, { method: 'DELETE' })
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    showToast('success', 'Đã xóa phòng');
                    setTimeout(() => location.reload(), 1000);
                }
            });
    }

    function filterRooms() {
        // Implement client-side filtering or reload with query params
        const search = document.getElementById('searchInput').value;
        const building = document.getElementById('buildingFilter').value;
        const status = document.getElementById('statusFilter').value;
        // Reload page with filters
        window.location.search = `?q=${search}&building_id=${building}&status=${status}`;
    }
</script>
