/**
 * KTX Management System — Main JS
 */

// ── Sidebar toggle ──────────────────────────────────────────
const sidebar  = document.getElementById('sidebar');
const overlay  = document.getElementById('sidebarOverlay');
const toggleBtn = document.getElementById('sidebarToggle');

function openSidebar() {
  sidebar?.classList.add('open');
  overlay?.classList.add('show');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  sidebar?.classList.remove('open');
  overlay?.classList.remove('show');
  document.body.style.overflow = '';
}
toggleBtn?.addEventListener('click', openSidebar);
overlay?.addEventListener('click', closeSidebar);

// ── Active sidebar link ─────────────────────────────────────
(function markActiveLink() {
  const path = window.location.pathname;
  document.querySelectorAll('.sidebar-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href && href !== '/' && path.startsWith(href)) {
      link.classList.add('active');
    } else if (href === path) {
      link.classList.add('active');
    }
  });
})();

// ── Flash / Alert dismiss ───────────────────────────────────
document.querySelectorAll('.alert-close').forEach(btn => {
  btn.addEventListener('click', () => {
    const alert = btn.closest('.alert');
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-6px)';
    setTimeout(() => alert.remove(), 250);
  });
});
// Auto-dismiss success alerts after 5s
setTimeout(() => {
  document.querySelectorAll('.alert-success').forEach(el => {
    el.style.transition = 'opacity .4s, transform .4s';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-6px)';
    setTimeout(() => el.remove(), 400);
  });
}, 5000);

// ── Modals ──────────────────────────────────────────────────
function openModal(id) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
  overlay.querySelector('[autofocus]')?.focus();
}
function closeModal(id) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.remove('open');
  document.body.style.overflow = '';
}
// Open via data-modal-open
document.addEventListener('click', e => {
  const trigger = e.target.closest('[data-modal-open]');
  if (trigger) openModal(trigger.dataset.modalOpen);

  const close = e.target.closest('[data-modal-close]');
  if (close) closeModal(close.dataset.modalClose);

  // Click outside modal content
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});
// ESC to close
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      m.classList.remove('open');
      document.body.style.overflow = '';
    });
    closeSidebar();
  }
});

// ── Dropdowns ───────────────────────────────────────────────
document.addEventListener('click', e => {
  const trigger = e.target.closest('[data-dropdown]');
  if (trigger) {
    const dropdown = trigger.closest('.dropdown');
    dropdown?.classList.toggle('open');
    e.stopPropagation();
    return;
  }
  document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
});

// ── Tabs ────────────────────────────────────────────────────
document.querySelectorAll('.tab-link').forEach(btn => {
  btn.addEventListener('click', () => {
    const tabGroup = btn.closest('.tabs');
    const tabContent = document.querySelector(btn.dataset.tabTarget);
    if (!tabContent) return;

    tabGroup.querySelectorAll('.tab-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const paneContainer = tabContent.closest('.tab-content') || tabContent.parentElement;
    paneContainer.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    tabContent.classList.add('active');
  });
});

// ── Confirm delete ──────────────────────────────────────────
document.addEventListener('click', e => {
  const btn = e.target.closest('[data-confirm]');
  if (btn) {
    const msg = btn.dataset.confirm || 'Bạn có chắc muốn thực hiện thao tác này?';
    if (!confirm(msg)) e.preventDefault();
  }
});

// ── AJAX fetch helper ───────────────────────────────────────
async function ktxFetch(url, options = {}) {
  const defaults = {
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    },
  };
  const res = await fetch(url, { ...defaults, ...options, headers: { ...defaults.headers, ...(options.headers || {}) } });
  const json = await res.json();
  if (!res.ok) throw json;
  return json;
}

// ── Toast notification ──────────────────────────────────────
function toast(message, type = 'success') {
  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  const div = document.createElement('div');
  div.className = `alert alert-${type}`;
  div.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;max-width:360px;box-shadow:var(--shadow-lg)';
  div.innerHTML = `
    <span class="alert-icon">${icons[type] || '💬'}</span>
    <div class="alert-content"><p class="alert-msg">${message}</p></div>
    <button class="alert-close" onclick="this.closest('.alert').remove()">×</button>
  `;
  document.body.appendChild(div);
  setTimeout(() => { div.style.opacity='0'; div.style.transform='translateX(16px)'; setTimeout(() => div.remove(), 300); }, 4000);
}

// ── Form submit with loading state ─────────────────────────
document.querySelectorAll('form[data-ajax]').forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = form.querySelector('[type=submit]');
    const origText = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="loading"></span> Đang xử lý...'; }

    try {
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());
      const res = await ktxFetch(form.action, { method: form.method.toUpperCase(), body: JSON.stringify(data) });
      toast(res.message || 'Thành công', 'success');
      if (res.redirect) window.location.href = res.redirect;
      if (form.dataset.ajaxReset !== undefined) form.reset();
    } catch (err) {
      toast(err.message || 'Đã có lỗi xảy ra', 'error');
      // Show field errors
      if (err.errors) {
        Object.entries(err.errors).forEach(([field, msgs]) => {
          const input = form.querySelector(`[name="${field}"]`);
          if (input) {
            input.classList.add('is-invalid');
            let errEl = input.parentElement.querySelector('.form-error');
            if (!errEl) { errEl = document.createElement('p'); errEl.className = 'form-error'; input.after(errEl); }
            errEl.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
          }
        });
      }
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = origText; }
    }
  });
});

// Clear form errors on input
document.addEventListener('input', e => {
  if (e.target.classList.contains('is-invalid')) {
    e.target.classList.remove('is-invalid');
    e.target.parentElement.querySelector('.form-error')?.remove();
  }
});

// ── Animate stat numbers ─────────────────────────────────────
function animateCount(el) {
  const target = parseInt(el.dataset.count || el.textContent.replace(/\D/g,''), 10);
  if (isNaN(target)) return;
  const duration = 1200;
  const start = performance.now();
  const step = ts => {
    const progress = Math.min((ts - start) / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    el.textContent = Math.round(eased * target).toLocaleString('vi-VN');
    if (progress < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => { if (entry.isIntersecting) { animateCount(entry.target); observer.unobserve(entry.target); } });
});
document.querySelectorAll('[data-count]').forEach(el => observer.observe(el));

// ── Format currency ─────────────────────────────────────────
function formatVND(amount) {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// ── Export functions to global ───────────────────────────────
window.ktx = { openModal, closeModal, toast, ktxFetch, formatVND };
