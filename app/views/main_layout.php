<?php
/**
 * app/views/layouts/main.php
 * ─────────────────────────────────────────────────────────────
 *  Master layout template — wraps all views
 *  Includes header, sidebar, footer
 *  Responsive design optimized for desktop & tablet
 * ─────────────────────────────────────────────────────────────
 */
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= htmlspecialchars($title ?? 'KTX Management') ?> — KTX System</title>
    
    <!-- Bootstrap CSS for responsive grid -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tabler Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/1.110.0/tabler-icons.min.css" rel="stylesheet">
    
    <!-- Chart.js for admin dashboard -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --color-primary: #185FA5;
            --color-success: #3B6D11;
            --color-warning: #BA7517;
            --color-danger: #A32D2D;
            --color-info: #378ADD;
            --color-light: #F1EFE8;
            --color-dark: #2C2C2A;
            --color-border: #D3D1C7;
            --color-text: #3d3d3a;
            --color-text-muted: #888780;
            --sidebar-width: 260px;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #F8F7F2;
            color: var(--color-text);
        }

        .app-wrapper {
            display: flex;
            height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 0.5px solid var(--color-border);
            overflow-y: auto;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 0.5px solid var(--color-border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-logo {
            font-size: 20px;
            font-weight: 600;
            color: var(--color-primary);
        }

        .sidebar-nav {
            list-style: none;
            padding: 1rem 0;
        }

        .sidebar-nav-item {
            position: relative;
            margin: 0;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1.5rem;
            color: var(--color-text);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-nav-link:hover {
            background: #F8F7F2;
            color: var(--color-primary);
            border-left-color: var(--color-primary);
        }

        .sidebar-nav-link.active {
            background: #F0F4F8;
            color: var(--color-primary);
            border-left-color: var(--color-primary);
            font-weight: 500;
        }

        .sidebar-nav-link i {
            font-size: 18px;
            flex-shrink: 0;
        }

        .sidebar-section-title {
            padding: 1rem 1.5rem 0.5rem;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--color-text-muted);
            letter-spacing: 0.5px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            border-top: 0.5px solid var(--color-border);
            background: white;
        }

        .user-mini {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #E6F1FB;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 13px;
            color: var(--color-primary);
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-size: 13px;
            font-weight: 500;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 11px;
            color: var(--color-text-muted);
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
        }

        .topbar {
            background: white;
            border-bottom: 0.5px solid var(--color-border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-shrink: 0;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 500;
        }

        .topbar-search {
            flex: 1;
            max-width: 300px;
            position: relative;
        }

        .topbar-search input {
            width: 100%;
            padding: 0.5rem 0.75rem 0.5rem 2.5rem;
            border: 0.5px solid var(--color-border);
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.2s;
        }

        .topbar-search input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.1);
        }

        .topbar-search i {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--color-text-muted);
            font-size: 16px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .action-btn {
            background: none;
            border: none;
            color: var(--color-text);
            cursor: pointer;
            font-size: 18px;
            transition: color 0.2s;
            position: relative;
        }

        .action-btn:hover {
            color: var(--color-primary);
        }

        .badge-dot {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 10px;
            height: 10px;
            background: var(--color-danger);
            border-radius: 50%;
            border: 2px solid white;
        }

        /* ── CONTENT AREA ── */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
        }

        /* ── FLASH MESSAGES ── */
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border-left: 4px solid;
            font-size: 14px;
        }

        .alert-success {
            background: #EAF3DE;
            border-left-color: var(--color-success);
            color: #27500A;
        }

        .alert-danger {
            background: #FCEBEB;
            border-left-color: var(--color-danger);
            color: #501313;
        }

        .alert-warning {
            background: #FAEEDA;
            border-left-color: var(--color-warning);
            color: #412402;
        }

        .alert-info {
            background: #E6F1FB;
            border-left-color: var(--color-info);
            color: #042C53;
        }

        .alert i {
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 500;
            display: block;
            margin-bottom: 2px;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
        }

        .page-subtitle {
            font-size: 13px;
            color: var(--color-text-muted);
            margin-top: 4px;
        }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem 1rem;
            border: 0.5px solid var(--color-border);
            border-radius: 4px;
            background: white;
            color: var(--color-text);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover {
            background: #F8F7F2;
            border-color: var(--color-text-muted);
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        .btn-primary:hover {
            background: #154FA0;
            border-color: #154FA0;
        }

        .btn-success {
            background: var(--color-success);
            color: white;
            border-color: var(--color-success);
        }

        .btn-danger {
            background: var(--color-danger);
            color: white;
            border-color: var(--color-danger);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 12px;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        /* ── CARDS ── */
        .card {
            background: white;
            border: 0.5px solid var(--color-border);
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 0.5px solid var(--color-border);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
        }

        /* ── METRICS ── */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            border: 0.5px solid var(--color-border);
            border-radius: 6px;
            padding: 1.25rem;
        }

        .metric-label {
            font-size: 12px;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: block;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .metric-change {
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .metric-change.positive {
            color: var(--color-success);
        }

        .metric-change.negative {
            color: var(--color-danger);
        }

        /* ── TABLE ── */
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .table th {
            background: #F8F7F2;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--color-text-muted);
            border-bottom: 0.5px solid var(--color-border);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 0.75rem 1rem;
            border-bottom: 0.5px solid var(--color-border);
        }

        .table tbody tr:hover {
            background: #FEFDFB;
        }

        .table-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: #EAF3DE;
            color: #27500A;
        }

        .status-pending {
            background: #FAEEDA;
            color: #412402;
        }

        .status-inactive {
            background: #F1EFE8;
            color: #5F5E5A;
        }

        .status-error {
            background: #FCEBEB;
            color: #501313;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0;
            }

            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .topbar {
                padding: 1rem;
            }

            .content {
                padding: 1rem;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── SCROLLBAR ── */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #D3D1C7;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #A9A69D;
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- ── SIDEBAR ── -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="ti ti-building-community" style="font-size: 24px; color: var(--color-primary);"></i>
                <span class="sidebar-logo">KTX</span>
            </div>

            <nav class="sidebar-nav">
                <?php if ($this->auth() && $this->auth('role') === 'admin'): ?>
                    <div class="sidebar-section-title">Dashboard</div>
                    <li class="sidebar-nav-item">
                        <a href="/admin/dashboard" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'dashboard') ? 'active' : '' ?>">
                            <i class="ti ti-layout-dashboard"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <div class="sidebar-section-title">Quản lý</div>
                    <li class="sidebar-nav-item">
                        <a href="/admin/buildings" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'buildings') ? 'active' : '' ?>">
                            <i class="ti ti-building"></i>
                            <span>Tòa nhà</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/admin/rooms" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'rooms') ? 'active' : '' ?>">
                            <i class="ti ti-door"></i>
                            <span>Phòng</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/admin/students" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'students') ? 'active' : '' ?>">
                            <i class="ti ti-users"></i>
                            <span>Sinh viên</span>
                        </a>
                    </li>

                    <div class="sidebar-section-title">Đăng ký & Hợp đồng</div>
                    <li class="sidebar-nav-item">
                        <a href="/admin/registrations" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'registrations') ? 'active' : '' ?>">
                            <i class="ti ti-checklist"></i>
                            <span>Đăng ký phòng</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/admin/contracts" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'contracts') ? 'active' : '' ?>">
                            <i class="ti ti-file-contract"></i>
                            <span>Hợp đồng</span>
                        </a>
                    </li>

                    <div class="sidebar-section-title">Tài chính</div>
                    <li class="sidebar-nav-item">
                        <a href="/admin/invoices" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'invoices') ? 'active' : '' ?>">
                            <i class="ti ti-receipt"></i>
                            <span>Hóa đơn</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/admin/utilities" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'utilities') ? 'active' : '' ?>">
                            <i class="ti ti-plug"></i>
                            <span>Điện/Nước</span>
                        </a>
                    </li>

                    <div class="sidebar-section-title">Quản lý</div>
                    <li class="sidebar-nav-item">
                        <a href="/admin/violations" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'violations') ? 'active' : '' ?>">
                            <i class="ti ti-alert-triangle"></i>
                            <span>Vi phạm</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/admin/maintenance" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'maintenance') ? 'active' : '' ?>">
                            <i class="ti ti-tool"></i>
                            <span>Bảo trì</span>
                        </a>
                    </li>

                <?php else: ?>
                    <div class="sidebar-section-title">Sinh viên</div>
                    <li class="sidebar-nav-item">
                        <a href="/student/dashboard" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'dashboard') ? 'active' : '' ?>">
                            <i class="ti ti-layout-dashboard"></i>
                            <span>Bảng điều khiển</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/student/registrations" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'registrations') ? 'active' : '' ?>">
                            <i class="ti ti-checklist"></i>
                            <span>Đăng ký phòng</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/student/contracts" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'contracts') ? 'active' : '' ?>">
                            <i class="ti ti-file-contract"></i>
                            <span>Hợp đồng</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/student/invoices" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'invoices') ? 'active' : '' ?>">
                            <i class="ti ti-receipt"></i>
                            <span>Hóa đơn</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/student/maintenance" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'maintenance') ? 'active' : '' ?>">
                            <i class="ti ti-tool"></i>
                            <span>Báo cáo lỗi</span>
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="/student/profile" class="sidebar-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'profile') ? 'active' : '' ?>">
                            <i class="ti ti-user"></i>
                            <span>Hồ sơ cá nhân</span>
                        </a>
                    </li>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <?php if ($this->auth()): ?>
                    <div class="user-mini">
                        <div class="user-avatar">
                            <?= strtoupper(substr($this->auth('username') ?? 'U', 0, 2)) ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($this->auth('username') ?? 'User') ?></span>
                            <span class="user-role"><?= $this->auth('role') === 'admin' ? 'Admin' : 'Sinh viên' ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- ── MAIN CONTENT ── -->
        <div class="main-content">
            <!-- ── TOPBAR ── -->
            <div class="topbar">
                <div class="topbar-left">
                    <h2 class="topbar-title"><?= htmlspecialchars($title ?? 'KTX Management') ?></h2>
                    <div class="topbar-search">
                        <i class="ti ti-search"></i>
                        <input type="text" placeholder="Tìm kiếm..." id="globalSearch">
                    </div>
                </div>
                <div class="topbar-actions">
                    <button class="action-btn" onclick="sendPrompt('Show me unread notifications')">
                        <i class="ti ti-bell"></i>
                        <div class="badge-dot"></div>
                    </button>
                    <button class="action-btn" onclick="sendPrompt('Show user settings menu')">
                        <i class="ti ti-settings"></i>
                    </button>
                    <form action="/logout" method="POST" style="display: inline;">
                        <button type="submit" class="action-btn" title="Đăng xuất">
                            <i class="ti ti-logout"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- ── CONTENT ── -->
            <div class="content">
                <?php foreach (($_flash ?? []) as $msg): ?>
                    <div class="alert alert-<?= htmlspecialchars($msg['type'] ?? 'info') ?>">
                        <i class="ti ti-<?= $msg['type'] === 'success' ? 'check' : ($msg['type'] === 'danger' ? 'alert-triangle' : 'info-circle') ?>"></i>
                        <div class="alert-content">
                            <span class="alert-title"><?= htmlspecialchars($msg['message'] ?? '') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Page content goes here (injected by view()) -->
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <script>
        // Global search (demo)
        document.getElementById('globalSearch').addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                const query = e.target.value.trim();
                if (query) {
                    sendPrompt(`Search for "${query}" across the system`);
                }
            }
        });

        // Toast notification helper
        function showToast(type, message) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="ti ti-${type === 'success' ? 'check' : 'alert-triangle'}"></i>
                <div class="alert-content">
                    <span class="alert-title">${message}</span>
                </div>
            `;
            document.querySelector('.content').prepend(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        // CSRF token getter (for AJAX)
        function getCsrfToken() {
            return document.querySelector('[name="_csrf_token"]')?.value || '';
        }

        // Helper: format VND currency
        function formatVnd(amount) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(amount);
        }

        // Helper: format date to Vietnamese locale
        function formatDate(date) {
            return new Date(date).toLocaleDateString('vi-VN');
        }
    </script>
</body>
</html>
