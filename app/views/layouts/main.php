<?php
/**
 * app/views/layouts/main.php
 * Main layout — sidebar + topbar + content area
 * Variables available: $content, $_auth, $_flash, $_errors, $_old, $_csrfToken
 */

$role       = $_auth['role'] ?? 'student';
$userName   = $_auth['username'] ?? 'User';
$userInitial = strtoupper(mb_substr($userName, 0, 1));
$isAdmin    = $role === 'admin';

// Current URI for active link detection (subfolder-aware)
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$scriptDir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$rootDir    = rtrim(dirname($scriptDir), '/\\');

if ($scriptDir !== '' && $scriptDir !== '/' && str_starts_with($currentUri, $scriptDir)) {
    $currentUri = substr($currentUri, strlen($scriptDir));
} elseif ($rootDir !== '' && $rootDir !== '/' && $rootDir !== '\\' && str_starts_with($currentUri, $rootDir)) {
    $currentUri = substr($currentUri, strlen($rootDir));
}
$currentUri = '/' . trim($currentUri, '/') ?: '/';

function navLink(string $href, string $icon, string $label, string $current, string $badge = ''): string {
    $dynamicHref = getDynamicUrl($href);
    $active = ($href !== '/' && str_starts_with($current, $href)) ? ' active' : ($current === $href ? ' active' : '');
    $badgeHtml = $badge ? "<span class=\"nav-badge\">{$badge}</span>" : '';
    return "<a href=\"{$dynamicHref}\" class=\"sidebar-link{$active}\">
              <span class=\"nav-icon\">{$icon}</span>
              <span>{$label}</span>
              {$badgeHtml}
            </a>";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= htmlspecialchars($_csrfToken ?? '') ?>">
<title><?= htmlspecialchars($title ?? 'KTX Management') ?> — KTX System</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
<link rel="stylesheet" href="<?= getDynamicUrl('/assets/css/app.css') ?>">
</head>
<body>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="layout">

  <!-- ── Sidebar ─────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon">🏠</div>
      <div class="sidebar-logo-text">
        <strong>KTX System</strong>
        <span>Quản lý ký túc xá</span>
      </div>
    </div>

    <!-- User info -->
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= $userInitial ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= htmlspecialchars($userName) ?></div>
        <div class="sidebar-user-role"><?= $isAdmin ? 'Quản trị viên' : 'Sinh viên' ?></div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

      <?php if ($isAdmin): ?>

      <div class="sidebar-section-label">Tổng quan</div>
      <?= navLink('/admin/dashboard', '📊', 'Dashboard', $currentUri) ?>

      <div class="sidebar-section-label">Quản lý</div>
      <?= navLink('/admin/users',     '👥', 'Người dùng',   $currentUri) ?>
      <?= navLink('/admin/students',  '🎓', 'Sinh viên',    $currentUri) ?>
      <?= navLink('/admin/buildings', '🏢', 'Tòa nhà',      $currentUri) ?>
      <?= navLink('/admin/rooms',     '🚪', 'Phòng ở',      $currentUri) ?>

      <div class="sidebar-section-label">Nghiệp vụ</div>
      <?= navLink('/admin/registrations', '📋', 'Đăng ký phòng', $currentUri) ?>
      <?= navLink('/admin/contracts',     '📄', 'Hợp đồng',      $currentUri) ?>
      <?= navLink('/admin/utilities',     '⚡', 'Điện nước',      $currentUri) ?>
      <?= navLink('/admin/invoices',      '🧾', 'Hóa đơn',       $currentUri) ?>
      <?= navLink('/admin/violations',    '⚠️', 'Vi phạm',        $currentUri) ?>
      <?= navLink('/admin/maintenance',   '🔧', 'Bảo trì',       $currentUri) ?>

      <div class="sidebar-section-label">Hệ thống</div>
      <?= navLink('/admin/notifications', '🔔', 'Thông báo',  $currentUri) ?>
      <?= navLink('/admin/reports/revenue', '📈', 'Báo cáo', $currentUri) ?>

      <?php else: /* Student nav */ ?>

      <div class="sidebar-section-label">Của tôi</div>
      <?= navLink('/student/dashboard',      '🏠', 'Trang chủ',    $currentUri) ?>
      <?= navLink('/student/profile',        '👤', 'Hồ sơ',        $currentUri) ?>

      <div class="sidebar-section-label">Phòng ở</div>
      <?= navLink('/student/registrations',  '📋', 'Đăng ký phòng', $currentUri) ?>
      <?= navLink('/student/contracts',      '📄', 'Hợp đồng',      $currentUri) ?>

      <div class="sidebar-section-label">Thanh toán</div>
      <?= navLink('/student/invoices',       '🧾', 'Hóa đơn',       $currentUri) ?>

      <div class="sidebar-section-label">Khác</div>
      <?= navLink('/student/violations',     '⚠️', 'Vi phạm',        $currentUri) ?>
      <?= navLink('/student/maintenance',    '🔧', 'Bảo trì',        $currentUri) ?>
      <?= navLink('/student/notifications',  '🔔', 'Thông báo',      $currentUri) ?>

      <?php endif; ?>

    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
      <form method="POST" action="<?= getDynamicUrl('/logout') ?>" style="margin:0">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
        <button type="submit" class="sidebar-link" style="width:100%;background:none;border:none;text-align:left">
          <span class="nav-icon">🚪</span>
          <span>Đăng xuất</span>
        </button>
      </form>
    </div>

  </aside>

  <!-- ── Main ────────────────────────────────────────────── -->
  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <button class="topbar-toggle" id="sidebarToggle" aria-label="Menu">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6"  x2="21" y2="6"/>
          <line x1="3" y1="12" x2="21" y2="12"/>
          <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>

      <div class="topbar-breadcrumb">
        <span><?= $isAdmin ? '⚙️ Admin' : '🎓 Sinh viên' ?></span>
        <span class="bc-sep">/</span>
        <span class="bc-current"><?= htmlspecialchars($title ?? '') ?></span>
      </div>

      <div class="topbar-right">
        <a href="<?= getDynamicUrl($isAdmin ? '/admin/notifications' : '/student/notifications') ?>" class="topbar-btn" title="Thông báo">
          🔔
          <span class="badge"></span>
        </a>

        <div class="dropdown">
          <div class="topbar-user-pill" data-dropdown>
            <div class="avatar avatar-sm"><?= $userInitial ?></div>
            <span><?= htmlspecialchars($userName) ?></span>
            <span style="color:var(--txt-muted);font-size:10px">▾</span>
          </div>
          <div class="dropdown-menu">
            <a href="<?= getDynamicUrl($isAdmin ? '/admin/dashboard' : '/student/profile') ?>" class="dropdown-item">
              👤 Hồ sơ
            </a>
            <div class="dropdown-divider"></div>
            <form method="POST" action="<?= getDynamicUrl('/logout') ?>" style="margin:0">
              <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">
              <button type="submit" class="dropdown-item danger" style="width:100%;background:none;border:none;text-align:left">
                🚪 Đăng xuất
              </button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <!-- Flash messages -->
    <div style="padding: 0 24px; padding-top: 16px;">
      <?php foreach ($_flash ?? [] as $f): ?>
        <?php
          $type = match($f['type'] ?? 'info') {
            'success' => ['alert-success', '✅'],
            'error'   => ['alert-error',   '❌'],
            'warning' => ['alert-warning', '⚠️'],
            default   => ['alert-info',    'ℹ️'],
          };
        ?>
        <div class="alert <?= $type[0] ?>">
          <span class="alert-icon"><?= $type[1] ?></span>
          <div class="alert-content"><p class="alert-msg"><?= htmlspecialchars($f['message']) ?></p></div>
          <button class="alert-close">×</button>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Page content -->
    <main class="page-body">
      <?= $content ?>
    </main>

    <!-- Footer -->
    <footer style="padding:16px 24px;border-top:1px solid var(--border);color:var(--txt-muted);font-size:12px;text-align:center">
      © <?= date('Y') ?> Hệ thống Quản lý KTX. All rights reserved.
    </footer>

  </div>
</div>

<script src="<?= getDynamicUrl('/assets/js/app.js') ?>"></script>
</body>
</html>
