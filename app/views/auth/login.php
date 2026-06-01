<?php
/** @var string $title */
/** @var array $_old */
/** @var array $_errors */
/** @var string $_csrfToken */
/** @var array $_flash */
?>
<div class="auth-card">
    <!-- Logo -->
    <div class="auth-logo">
        <span class="auth-logo-icon">🏠</span>
        <h1>KTX System</h1>
        <p>Hệ thống quản lý ký túc xá</p>
    </div>

    <!-- Flash Messages -->
    <?php foreach ($_flash ?? [] as $f): ?>
        <?php
          $type = match($f['type'] ?? 'info') {
            'success' => ['alert-success', '✅'],
            'error', 'danger' => ['alert-danger', '❌'],
            'warning' => ['alert-warning', '⚠️'],
            default   => ['alert-info',    'ℹ️'],
          };
        ?>
        <div class="alert <?= $type[0] ?>">
          <span><?= $type[1] ?></span>
          <span><?= htmlspecialchars($f['message']) ?></span>
        </div>
    <?php endforeach; ?>

    <!-- Title -->
    <h2 class="auth-title">Chào mừng trở lại 👋</h2>
    <p class="auth-subtitle">Đăng nhập để tiếp tục quản lý hệ thống</p>

    <!-- Login Form -->
    <form method="POST" action="<?= getDynamicUrl('/auth/login') ?>" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

        <!-- Email -->
        <div class="form-group">
            <label class="form-label" for="email">📧 Email</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control<?= !empty($_errors['email']) ? ' is-invalid' : '' ?>"
                value="<?= htmlspecialchars($_old['email'] ?? '') ?>"
                placeholder="email@example.com"
                autocomplete="email"
                autofocus
            >
            <?php if (!empty($_errors['email'])): ?>
                <span class="form-error">⚠️ <?= htmlspecialchars($_errors['email']) ?></span>
            <?php endif; ?>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label class="form-label" for="password">🔒 Mật khẩu</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control<?= !empty($_errors['password']) ? ' is-invalid' : '' ?>"
                    placeholder="••••••••"
                    autocomplete="current-password"
                >
                <button type="button" class="password-toggle" title="Hiện/ẩn mật khẩu">👁️</button>
            </div>
            <?php if (!empty($_errors['password'])): ?>
                <span class="form-error">⚠️ <?= htmlspecialchars($_errors['password']) ?></span>
            <?php endif; ?>
        </div>

        <!-- General error -->
        <?php if (!empty($_errors['general'])): ?>
            <div class="alert alert-danger">
                <span>❌</span>
                <span><?= htmlspecialchars($_errors['general']) ?></span>
            </div>
        <?php endif; ?>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary">
            🚀 Đăng nhập
        </button>
    </form>

    <!-- Register link -->
    <p class="auth-footer-text">
        Chưa có tài khoản?
        <a href="<?= getDynamicUrl('/auth/register') ?>" class="auth-link">Đăng ký ngay</a>
    </p>
</div>
