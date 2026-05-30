/**
 * public/assets/js/rooms-crud.js
 * ─────────────────────────────────────────────────────────────
 *  CRUD operations cho Rooms resource
 *  • Sử dụng Fetch API (không jQuery)
 *  • AJAX requests (không reload trang)
 *  • Toast notifications
 *  • Modal dialogs
 *  • Form validation
 *  • Filters & pagination
 *
 *  Cách sử dụng:
 *    <script src="/assets/js/rooms-crud.js"></script>
 *
 *  HTML structure:
 *    <div id="roomsContainer">...</div>
 *    <div id="roomModal">...</div>
 *    <div id="toast"></div>
 * ─────────────────────────────────────────────────────────────
 */

// ============================================================
//  CONFIGURATION
// ============================================================

const RoomsCRUD = {
  // API base URL
  apiUrl: '/api/rooms',
  
  // Page state
  currentPage: 1,
  perPage: 15,
  
  // Current filters
  filters: {
    building_id: null,
    status: null,
    type: null,
    has_ac: null,
    q: null,
    sort_by: 'r.id',
    sort_order: 'ASC',
  },

  // ============================================================
  //  INITIALIZATION
  // ============================================================

  /**
   * Initialize the rooms CRUD system
   */
  init() {
    console.log('[RoomsCRUD] Initializing...');
    this.setupEventListeners();
    this.loadRooms();
    console.log('[RoomsCRUD] Ready');
  },

  /**
   * Setup all event listeners
   */
  setupEventListeners() {
    // Create button
    document.getElementById('createRoomBtn')?.addEventListener('click', () => {
      this.openCreateModal();
    });

    // Filter inputs
    const filterInputs = document.querySelectorAll('[data-filter]');
    filterInputs.forEach(input => {
      input.addEventListener('change', () => {
        this.applyFilters();
      });
    });

    // Search input
    document.getElementById('roomSearch')?.addEventListener('keyup', (e) => {
      if (e.key === 'Enter') {
        this.applyFilters();
      }
    });

    // Search button
    document.getElementById('searchBtn')?.addEventListener('click', () => {
      this.applyFilters();
    });

    // Clear filters button
    document.getElementById('clearFiltersBtn')?.addEventListener('click', () => {
      this.clearFilters();
    });

    // Room form submit
    document.getElementById('roomForm')?.addEventListener('submit', (e) => {
      this.handleFormSubmit(e);
    });

    // Modal close buttons
    document.querySelectorAll('[data-modal-close]').forEach(btn => {
      btn.addEventListener('click', () => {
        this.closeModal(btn.closest('[data-modal]').id);
      });
    });

    // Export button
    document.getElementById('exportBtn')?.addEventListener('click', () => {
      this.exportRooms();
    });

    console.log('[RoomsCRUD] Event listeners attached');
  },

  // ============================================================
  //  LOAD & DISPLAY ROOMS
  // ============================================================

  /**
   * Load rooms from API
   */
  async loadRooms() {
    try {
      this.showLoading();

      const params = this.buildQueryString();
      const response = await fetch(`${this.apiUrl}?${params}`);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const json = await response.json();

      if (!json.success) {
        this.showToast('error', json.message);
        return;
      }

      this.renderRooms(json.data.data);
      this.renderPagination(json.data.pagination);
      this.hideLoading();

      console.log(`[RoomsCRUD] Loaded ${json.data.data.length} rooms`);
    } catch (error) {
      console.error('[RoomsCRUD] Error loading rooms:', error);
      this.showToast('error', 'Lỗi tải danh sách phòng: ' + error.message);
      this.hideLoading();
    }
  },

  /**
   * Render rooms table
   */
  renderRooms(rooms) {
    const tableBody = document.querySelector('table tbody');
    
    if (!tableBody) return;

    // Clear existing rows
    tableBody.innerHTML = '';

    if (rooms.length === 0) {
      tableBody.innerHTML = `
        <tr>
          <td colspan="10" style="text-align: center; padding: 2rem; color: var(--color-text-muted);">
            <i class="ti ti-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
            <p>Không tìm thấy phòng nào</p>
          </td>
        </tr>
      `;
      return;
    }

    // Add room rows
    rooms.forEach(room => {
      const row = document.createElement('tr');
      row.dataset.roomId = room.id;
      row.innerHTML = `
        <td>${room.room_number}</td>
        <td>${room.building_name}</td>
        <td>${room.floor}</td>
        <td>${this.getRoomTypeLabel(room.room_type)}</td>
        <td>
          <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 60px; height: 8px; background: var(--color-border); border-radius: 4px;">
              <div style="width: ${(room.occupancy_rate * 100)}%; height: 100%; background: ${this.getOccupancyColor(room.occupancy_rate)}; border-radius: 4px;"></div>
            </div>
            <span style="font-size: 12px;">${room.current_occupants}/${room.capacity}</span>
          </div>
        </td>
        <td>${this.formatCurrency(room.price_per_month)}</td>
        <td>
          ${room.has_ac ? '<span style="color: var(--color-success); font-size: 12px;">✓ Có AC</span>' : '<span style="color: var(--color-text-muted); font-size: 12px;">-</span>'}
        </td>
        <td>
          <span class="badge ${this.getStatusClass(room.status)}">
            ${this.getStatusLabel(room.status)}
          </span>
        </td>
        <td style="display: flex; gap: 4px;">
          <button class="btn btn-sm btn-icon" onclick="RoomsCRUD.viewRoom(${room.id})" title="Chi tiết">
            <i class="ti ti-eye"></i>
          </button>
          <button class="btn btn-sm btn-icon" onclick="RoomsCRUD.openEditModal(${room.id})" title="Sửa">
            <i class="ti ti-edit"></i>
          </button>
          <button class="btn btn-sm btn-icon btn-danger" onclick="RoomsCRUD.deleteRoom(${room.id})" title="Xóa">
            <i class="ti ti-trash"></i>
          </button>
        </td>
      `;
      tableBody.appendChild(row);
    });
  },

  /**
   * Render pagination
   */
  renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    container.innerHTML = '';

    // Previous button
    if (pagination.current_page > 1) {
      const btn = document.createElement('button');
      btn.className = 'btn btn-sm';
      btn.textContent = '← Trước';
      btn.onclick = () => this.goToPage(pagination.current_page - 1);
      container.appendChild(btn);
    }

    // Page buttons
    for (let i = 1; i <= pagination.last_page; i++) {
      const btn = document.createElement('button');
      btn.className = `btn btn-sm ${i === pagination.current_page ? 'btn-primary' : ''}`;
      btn.textContent = i;
      btn.onclick = () => this.goToPage(i);
      container.appendChild(btn);
    }

    // Next button
    if (pagination.current_page < pagination.last_page) {
      const btn = document.createElement('button');
      btn.className = 'btn btn-sm';
      btn.textContent = 'Sau →';
      btn.onclick = () => this.goToPage(pagination.current_page + 1);
      container.appendChild(btn);
    }
  },

  // ============================================================
  //  MODAL OPERATIONS
  // ============================================================

  /**
   * Open create room modal
   */
  openCreateModal() {
    this.resetForm();
    document.getElementById('modalTitle').textContent = 'Tạo phòng mới';
    document.getElementById('roomId').value = '';
    this.openModal('roomModal');
  },

  /**
   * Open edit room modal
   */
  async openEditModal(roomId) {
    try {
      const response = await fetch(`${this.apiUrl}/${roomId}`);
      const json = await response.json();

      if (!json.success) {
        this.showToast('error', 'Không tải được dữ liệu phòng');
        return;
      }

      const room = json.data;
      document.getElementById('modalTitle').textContent = `Sửa phòng ${room.room_number}`;
      document.getElementById('roomId').value = room.id;
      document.getElementById('buildingId').value = room.building_id;
      document.getElementById('roomNumber').value = room.room_number;
      document.getElementById('floor').value = room.floor;
      document.getElementById('roomType').value = room.room_type;
      document.getElementById('capacity').value = room.capacity;
      document.getElementById('pricePerMonth').value = room.price_per_month;
      document.getElementById('hasAc').checked = room.has_ac;

      this.openModal('roomModal');
    } catch (error) {
      console.error('Error loading room:', error);
      this.showToast('error', 'Lỗi: ' + error.message);
    }
  },

  /**
   * View room details (in modal or new page)
   */
  async viewRoom(roomId) {
    try {
      const response = await fetch(`${this.apiUrl}/${roomId}`);
      const json = await response.json();

      if (!json.success) {
        this.showToast('error', 'Không tải được thông tin phòng');
        return;
      }

      const room = json.data;
      let occupantsHtml = '<p style="margin: 0.5rem 0;">Chưa có sinh viên</p>';

      if (room.occupants && room.occupants.length > 0) {
        occupantsHtml = '<ul style="margin: 0; padding-left: 1.5rem;">' +
          room.occupants.map(s => `<li>${s.full_name} (${s.student_code})</li>`).join('') +
          '</ul>';
      }

      const detailsHtml = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
          <div>
            <p style="margin: 0; font-weight: 500; font-size: 12px; color: var(--color-text-muted);">Phòng</p>
            <p style="margin: 0.25rem 0 1rem 0; font-size: 18px; font-weight: 600;">${room.room_number}</p>

            <p style="margin: 0; font-weight: 500; font-size: 12px; color: var(--color-text-muted);">Tòa</p>
            <p style="margin: 0.25rem 0 1rem 0;">${room.building_name}</p>

            <p style="margin: 0; font-weight: 500; font-size: 12px; color: var(--color-text-muted);">Tầng</p>
            <p style="margin: 0.25rem 0 1rem 0;">${room.floor}</p>

            <p style="margin: 0; font-weight: 500; font-size: 12px; color: var(--color-text-muted);">Loại phòng</p>
            <p style="margin: 0.25rem 0 1rem 0;">${this.getRoomTypeLabel(room.room_type)}</p>
          </div>

          <div>
            <p style="margin: 0; font-weight: 500; font-size: 12px; color: var(--color-text-muted);">Sức chứa</p>
            <p style="margin: 0.25rem 0 1rem 0;">${room.capacity} giường</p>

            <p style="margin: 0; font-weight: 500; font-size: 12px; color: var(--color-text-muted);">Đang ở</p>
            <p style="margin: 0.25rem 0 1rem 0;">${room.current_occupants} sinh viên (${Math.round(room.occupancy_rate * 100)}%)</p>

            <p style="margin: 0; font-weight: 500; font-size: 12px; color: var(--color-text-muted);">Giá/tháng</p>
            <p style="margin: 0.25rem 0 1rem 0;">${this.formatCurrency(room.price_per_month)}</p>

            <p style="margin: 0; font-weight: 500; font-size: 12px; color: var(--color-text-muted);">Điều hòa</p>
            <p style="margin: 0.25rem 0 1rem 0;">${room.has_ac ? 'Có ✓' : 'Không'}</p>
          </div>
        </div>

        <div>
          <h4 style="margin: 1rem 0 0.5rem 0;">Sinh viên trong phòng (${room.occupants?.length || 0})</h4>
          ${occupantsHtml}
        </div>
      `;

      // Show in modal or alert
      const modal = document.getElementById('detailModal');
      if (modal) {
        document.getElementById('detailContent').innerHTML = detailsHtml;
        this.openModal('detailModal');
      } else {
        alert('Chi tiết phòng:\n\n' + JSON.stringify(room, null, 2));
      }
    } catch (error) {
      console.error('Error viewing room:', error);
      this.showToast('error', 'Lỗi: ' + error.message);
    }
  },

  /**
   * Open modal
   */
  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'flex';
      modal.classList.add('modal-open');
    }
  },

  /**
   * Close modal
   */
  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.style.display = 'none';
      modal.classList.remove('modal-open');
    }
  },

  // ============================================================
  //  FORM OPERATIONS
  // ============================================================

  /**
   * Reset form to empty state
   */
  resetForm() {
    const form = document.getElementById('roomForm');
    if (form) {
      form.reset();
    }
    this.clearFormErrors();
  },

  /**
   * Handle form submission (create or update)
   */
  async handleFormSubmit(e) {
    e.preventDefault();

    const roomId = document.getElementById('roomId').value;
    const method = roomId ? 'PUT' : 'POST';
    const url = roomId ? `${this.apiUrl}/${roomId}` : this.apiUrl;

    const formData = new FormData(document.getElementById('roomForm'));
    const data = Object.fromEntries(formData);

    // Type conversion
    data.building_id = parseInt(data.building_id);
    data.floor = parseInt(data.floor);
    data.capacity = parseInt(data.capacity);
    data.price_per_month = parseFloat(data.price_per_month);
    data.has_ac = formData.has('has_ac') ? 1 : 0;

    try {
      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify(data),
      });

      const json = await response.json();

      if (!json.success) {
        this.showFormErrors(json.errors || {});
        this.showToast('error', json.message);
        return;
      }

      this.showToast('success', json.message);
      this.closeModal('roomModal');
      this.resetForm();
      this.loadRooms();

      console.log(`[RoomsCRUD] Room ${roomId ? 'updated' : 'created'}`);
    } catch (error) {
      console.error('Error submitting form:', error);
      this.showToast('error', 'Lỗi: ' + error.message);
    }
  },

  /**
   * Show form validation errors
   */
  showFormErrors(errors) {
    this.clearFormErrors();

    Object.entries(errors).forEach(([field, messages]) => {
      const input = document.getElementById(
        field.replace(/_/g, '')
          .replace(/building/i, 'building')
          .replace(/room/i, 'room')
          .replace(/price/i, 'price')
      );

      if (input) {
        input.classList.add('error');
        const errorEl = document.createElement('small');
        errorEl.className = 'error-message';
        errorEl.textContent = messages[0];
        input.parentElement.appendChild(errorEl);
      }
    });
  },

  /**
   * Clear form errors
   */
  clearFormErrors() {
    document.querySelectorAll('.form-input').forEach(input => {
      input.classList.remove('error');
    });
    document.querySelectorAll('.error-message').forEach(el => {
      el.remove();
    });
  },

  // ============================================================
  //  DELETE OPERATION
  // ============================================================

  /**
   * Delete room (with confirmation)
   */
  async deleteRoom(roomId) {
    if (!confirm('Bạn chắc chắn muốn xóa phòng này?')) {
      return;
    }

    try {
      const response = await fetch(`${this.apiUrl}/${roomId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': getCsrfToken(),
        },
      });

      const json = await response.json();

      if (!json.success) {
        this.showToast('error', json.message);
        return;
      }

      this.showToast('success', json.message);
      this.loadRooms();

      console.log(`[RoomsCRUD] Deleted room #${roomId}`);
    } catch (error) {
      console.error('Error deleting room:', error);
      this.showToast('error', 'Lỗi: ' + error.message);
    }
  },

  // ============================================================
  //  FILTERS & PAGINATION
  // ============================================================

  /**
   * Apply filters and reload
   */
  applyFilters() {
    // Collect filter values
    this.filters.building_id = document.getElementById('filterBuilding')?.value || null;
    this.filters.status = document.getElementById('filterStatus')?.value || null;
    this.filters.type = document.getElementById('filterType')?.value || null;
    this.filters.has_ac = document.getElementById('filterAc')?.value || null;
    this.filters.q = document.getElementById('roomSearch')?.value || null;

    this.currentPage = 1;
    this.loadRooms();
  },

  /**
   * Clear all filters
   */
  clearFilters() {
    this.filters = {
      building_id: null,
      status: null,
      type: null,
      has_ac: null,
      q: null,
      sort_by: 'r.id',
      sort_order: 'ASC',
    };

    document.getElementById('filterBuilding').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterType').value = '';
    document.getElementById('filterAc').value = '';
    document.getElementById('roomSearch').value = '';

    this.currentPage = 1;
    this.loadRooms();
  },

  /**
   * Go to page
   */
  goToPage(page) {
    this.currentPage = page;
    this.loadRooms();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  },

  /**
   * Build query string from filters
   */
  buildQueryString() {
    const params = new URLSearchParams();
    params.append('page', this.currentPage);
    params.append('per_page', this.perPage);

    if (this.filters.building_id) params.append('building_id', this.filters.building_id);
    if (this.filters.status) params.append('status', this.filters.status);
    if (this.filters.type) params.append('type', this.filters.type);
    if (this.filters.has_ac !== null) params.append('has_ac', this.filters.has_ac);
    if (this.filters.q) params.append('q', this.filters.q);
    params.append('sort_by', this.filters.sort_by);
    params.append('sort_order', this.filters.sort_order);

    return params.toString();
  },

  // ============================================================
  //  EXPORT
  // ============================================================

  /**
   * Export rooms to CSV
   */
  exportRooms() {
    const csv = this.generateCSV();
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `rooms-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);

    this.showToast('success', 'Xuất file thành công');
  },

  /**
   * Generate CSV data
   */
  generateCSV() {
    const headers = ['Phòng', 'Tòa', 'Tầng', 'Loại', 'Sức chứa', 'Đang ở', 'Giá/tháng', 'AC', 'Trạng thái'];
    const rows = [];

    document.querySelectorAll('table tbody tr').forEach(tr => {
      const cells = tr.querySelectorAll('td');
      if (cells.length > 0) {
        rows.push([
          cells[0].textContent,
          cells[1].textContent,
          cells[2].textContent,
          cells[3].textContent,
          cells[4].textContent,
          cells[5].textContent,
          cells[6].textContent,
          cells[7].textContent,
        ]);
      }
    });

    return [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${cell.trim()}"`).join(',')),
    ].join('\n');
  },

  // ============================================================
  //  UI HELPERS
  // ============================================================

  /**
   * Show loading indicator
   */
  showLoading() {
    const loader = document.getElementById('loadingIndicator');
    if (loader) loader.style.display = 'block';
  },

  /**
   * Hide loading indicator
   */
  hideLoading() {
    const loader = document.getElementById('loadingIndicator');
    if (loader) loader.style.display = 'none';
  },

  /**
   * Show toast notification
   */
  showToast(type, message) {
    const toastContainer = document.getElementById('toast') || this.createToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <div style="display: flex; align-items: center; gap: 12px;">
        ${type === 'success' ? '<i class="ti ti-check"></i>' : '<i class="ti ti-alert-circle"></i>'}
        <span>${message}</span>
      </div>
    `;

    toastContainer.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  },

  /**
   * Create toast container if doesn't exist
   */
  createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast';
    container.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 12px;
    `;
    document.body.appendChild(container);
    return container;
  },

  /**
   * Get room type label
   */
  getRoomTypeLabel(type) {
    const labels = {
      'standard': 'Tiêu chuẩn',
      'deluxe': 'Cao cấp',
      'ac_standard': 'Tiêu chuẩn AC',
      'ac_deluxe': 'Cao cấp AC',
    };
    return labels[type] || type;
  },

  /**
   * Get status label
   */
  getStatusLabel(status) {
    const labels = {
      'available': 'Trống',
      'full': 'Đầy',
      'maintenance': 'Bảo trì',
      'inactive': 'Không dùng',
    };
    return labels[status] || status;
  },

  /**
   * Get status CSS class
   */
  getStatusClass(status) {
    const classes = {
      'available': 'badge-success',
      'full': 'badge-warning',
      'maintenance': 'badge-info',
      'inactive': 'badge-danger',
    };
    return classes[status] || 'badge-default';
  },

  /**
   * Get occupancy color
   */
  getOccupancyColor(rate) {
    if (rate < 0.5) return '#3B6D11'; // Green
    if (rate < 0.8) return '#BA7517'; // Orange
    return '#A32D2D'; // Red
  },

  /**
   * Format currency
   */
  formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
      style: 'currency',
      currency: 'VND',
      minimumFractionDigits: 0,
    }).format(amount);
  },
};

// ============================================================
//  UTILITIES
// ============================================================

/**
 * Get CSRF token from DOM
 */
function getCsrfToken() {
  return document.querySelector('[name="_csrf_token"]')?.value ||
         document.querySelector('[data-csrf]')?.getAttribute('data-csrf') ||
         '';
}

/**
 * Initialize when DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
  RoomsCRUD.init();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = RoomsCRUD;
}
