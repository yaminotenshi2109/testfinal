/**
 * public/assets/js/ajax-utilities.js
 * ─────────────────────────────────────────────────────────────
 *  Các hàm helper cho AJAX CRUD trong hệ thống KTX
 *  Tái sử dụng cho tất cả các module
 * ─────────────────────────────────────────────────────────────
 */

/**
 * Fetch với error handling
 * 
 * @param {string} url - Đường dẫn API
 * @param {object} options - fetch options
 * @returns {Promise}
 */
async function apiFetch(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    };

    const finalOptions = { ...defaultOptions, ...options };

    try {
        const response = await fetch(url, finalOptions);
        const data = await response.json();

        if (!response.ok && data.errors) {
            throw new ApiError(data.message || 'Request failed', data.errors, response.status);
        }

        return data;
    } catch (error) {
        if (error instanceof ApiError) {
            throw error;
        }
        throw new ApiError(error.message, {}, 0);
    }
}

/**
 * API Error class
 */
class ApiError extends Error {
    constructor(message, errors = {}, status = 0) {
        super(message);
        this.errors = errors;
        this.status = status;
    }
}

/**
 * ───────────────────────────────────────────────────────────
 *  CRUD Operations
 * ───────────────────────────────────────────────────────────
 */

/**
 * GET - Lấy danh sách hoặc chi tiết
 */
async function apiGet(url) {
    return apiFetch(url, { method: 'GET' });
}

/**
 * POST - Tạo resource mới
 */
async function apiCreate(url, data) {
    return apiFetch(url, {
        method: 'POST',
        body: JSON.stringify(data),
    });
}

/**
 * PUT - Cập nhật resource
 */
async function apiUpdate(url, data) {
    return apiFetch(url, {
        method: 'PUT',
        body: JSON.stringify(data),
    });
}

/**
 * DELETE - Xóa resource
 */
async function apiDelete(url) {
    return apiFetch(url, { method: 'DELETE' });
}

/**
 * ───────────────────────────────────────────────────────────
 *  UI Feedback
 * ───────────────────────────────────────────────────────────
 */

/**
 * Hiển thị toast notification
 * 
 * @param {string} type - 'success', 'danger', 'warning', 'info'
 * @param {string} message - Nội dung thông báo
 * @param {number} duration - Thời gian hiển thị (ms)
 */
function showToast(type, message, duration = 5000) {
    const icons = {
        'success': 'ti-check',
        'danger': 'ti-alert-triangle',
        'warning': 'ti-alert-circle',
        'info': 'ti-info-circle',
    };

    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        max-width: 400px;
        z-index: 2000;
        animation: slideIn 0.3s ease;
    `;
    alert.innerHTML = `
        <i class="ti ${icons[type] || 'ti-info-circle'}"></i>
        <div class="alert-content">
            <span class="alert-title">${type === 'success' ? 'Thành công' : type === 'danger' ? 'Lỗi' : type === 'warning' ? 'Cảnh báo' : 'Thông báo'}</span>
            ${message}
        </div>
    `;

    document.body.appendChild(alert);

    setTimeout(() => {
        alert.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => alert.remove(), 300);
    }, duration);
}

/**
 * Hiển thị lỗi validation
 * 
 * @param {object} errors - { fieldName: [error1, error2], ... }
 */
function showValidationErrors(errors) {
    Object.entries(errors).forEach(([field, messages]) => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('error');
            const errorEl = document.getElementById(field + 'Error');
            if (errorEl) {
                errorEl.textContent = messages[0];
                errorEl.style.display = 'block';
            }
        }
    });
}

/**
 * Xóa lỗi validation
 */
function clearValidationErrors() {
    document.querySelectorAll('[id$="Error"]').forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
    });
    document.querySelectorAll('.error').forEach(el => {
        el.classList.remove('error');
    });
}

/**
 * ───────────────────────────────────────────────────────────
 *  Form Handling
 * ───────────────────────────────────────────────────────────
 */

/**
 * Lấy dữ liệu form dưới dạng object
 * 
 * @param {HTMLFormElement} form
 * @returns {object}
 */
function getFormData(form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    // Convert checkboxes
    document.querySelectorAll(`[form="${form.id}"] input[type="checkbox"]`).forEach(cb => {
        if (!data.hasOwnProperty(cb.name)) {
            data[cb.name] = false;
        } else if (cb.checked) {
            data[cb.name] = true;
        }
    });

    return data;
}

/**
 * Điền dữ liệu vào form
 * 
 * @param {HTMLFormElement} form
 * @param {object} data
 */
function fillForm(form, data) {
    Object.entries(data).forEach(([key, value]) => {
        const field = form.elements[key];
        if (!field) return;

        if (field.type === 'checkbox') {
            field.checked = !!value;
        } else if (field.type === 'radio') {
            const radio = form.querySelector(`input[name="${key}"][value="${value}"]`);
            if (radio) radio.checked = true;
        } else {
            field.value = value || '';
        }
    });
}

/**
 * Reset form
 * 
 * @param {HTMLFormElement} form
 */
function resetForm(form) {
    form.reset();
    clearValidationErrors();
}

/**
 * ───────────────────────────────────────────────────────────
 *  Pagination & Filtering
 * ───────────────────────────────────────────────────────────
 */

/**
 * Build query string từ form inputs
 * 
 * @returns {string} - "?key1=val1&key2=val2"
 */
function buildQueryString() {
    const params = new URLSearchParams();

    // Collect từ input#searchInput, select#filterName, etc.
    const search = document.getElementById('searchInput')?.value;
    const filters = document.querySelectorAll('[id$="Filter"]');

    if (search) params.append('q', search);
    filters.forEach(f => {
        if (f.value) {
            const name = f.id.replace('Filter', '').toLowerCase();
            params.append(name, f.value);
        }
    });

    return params.toString() ? '?' + params.toString() : '';
}

/**
 * Go to page (with filters preserved)
 * 
 * @param {number} page
 */
function goToPage(page) {
    const query = buildQueryString();
    const separator = query ? '&' : '?';
    window.location = window.location.pathname + query + separator + 'page=' + page;
}

/**
 * Filter/Search (reload với query params)
 */
function filterTable() {
    const query = buildQueryString();
    window.location = window.location.pathname + query;
}

/**
 * ───────────────────────────────────────────────────────────
 *  Table & List Utilities
 * ───────────────────────────────────────────────────────────
 */

/**
 * Toggle Select All checkboxes
 * 
 * @param {string} selectAllId - ID của checkbox select all
 * @param {string} checkboxClass - CSS class của individual checkboxes
 * @param {Function} callback - Hàm callback khi có thay đổi
 */
function setupSelectAll(selectAllId, checkboxClass, callback = null) {
    const selectAll = document.getElementById(selectAllId);
    if (!selectAll) return;

    selectAll.addEventListener('change', function() {
        const checked = this.checked;
        document.querySelectorAll('.' + checkboxClass).forEach(cb => {
            cb.checked = checked;
        });
        if (callback) callback();
    });

    // Cập nhật select all khi uncheck một item
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains(checkboxClass)) {
            const allChecked = Array.from(document.querySelectorAll('.' + checkboxClass))
                .every(cb => cb.checked);
            selectAll.checked = allChecked;
            if (callback) callback();
        }
    });
}

/**
 * Get selected IDs
 * 
 * @param {string} checkboxClass - CSS class của checkboxes
 * @returns {array} - [1, 2, 3, ...]
 */
function getSelectedIds(checkboxClass) {
    return Array.from(document.querySelectorAll('.' + checkboxClass + ':checked'))
        .map(cb => parseInt(cb.closest('[data-id]')?.dataset.id || 0))
        .filter(id => id > 0);
}

/**
 * ───────────────────────────────────────────────────────────
 *  Modal Dialogs
 * ───────────────────────────────────────────────────────────
 */

/**
 * Open modal with form
 * 
 * @param {string} modalId - ID của modal div
 * @param {string} titleId - ID của tiêu đề
 * @param {string} title - Nội dung tiêu đề
 * @param {object} data - Dữ liệu để điền vào form (optional)
 */
function openModal(modalId, titleId, title, data = null) {
    const modal = document.getElementById(modalId);
    const titleEl = document.getElementById(titleId);

    if (titleEl) titleEl.textContent = title;

    // Clear lỗi
    clearValidationErrors();

    // Điền dữ liệu nếu có
    if (data) {
        const form = modal.querySelector('form');
        if (form) fillForm(form, data);
    }

    if (modal) modal.style.display = 'flex';
}

/**
 * Close modal
 * 
 * @param {string} modalId - ID của modal div
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

/**
 * Confirm dialog
 * 
 * @param {string} message - Nội dung câu hỏi
 * @returns {boolean}
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * ───────────────────────────────────────────────────────────
 *  Utilities
 * ───────────────────────────────────────────────────────────
 */

/**
 * Format currency
 * 
 * @param {number} amount - Số tiền
 * @returns {string} - "600.000 VND"
 */
function formatVnd(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

/**
 * Format date
 * 
 * @param {string} date - ISO date string
 * @returns {string} - "01/01/2025"
 */
function formatDate(date) {
    return new Date(date).toLocaleDateString('vi-VN');
}

/**
 * Delay (for promise chains)
 * 
 * @param {number} ms - Milliseconds
 * @returns {Promise}
 */
function delay(ms = 1000) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Debounce function
 * 
 * @param {Function} fn - Hàm cần debounce
 * @param {number} delay - Độ trễ (ms)
 * @returns {Function}
 */
function debounce(fn, delay = 500) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

/**
 * Copy to clipboard
 * 
 * @param {string} text - Text cần copy
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('success', 'Đã copy vào clipboard', 2000);
    } catch (err) {
        console.error('Copy failed:', err);
    }
}

/**
 * ───────────────────────────────────────────────────────────
 *  CSS Animations
 * ───────────────────────────────────────────────────────────
 */

// Add CSS animations to document if not already added
if (!document.querySelector('style[data-ajax-utils]')) {
    const style = document.createElement('style');
    style.setAttribute('data-ajax-utils', 'true');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        input.error,
        select.error,
        textarea.error {
            border-color: var(--color-danger, #A32D2D) !important;
        }
    `;
    document.head.appendChild(style);
}
