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
    <h2 class="auth-title">Tạo tài khoản mới ✨</h2>
    <p class="auth-subtitle">Điền thông tin để bắt đầu sử dụng hệ thống</p>

    <!-- Register Form -->
    <form method="POST" action="<?= getDynamicUrl('/auth/register') ?>" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

        <!-- Username -->
        <div class="form-group">
            <label class="form-label" for="username">👤 Tên đăng nhập</label>
            <input
                type="text"
                id="username"
                name="username"
                class="form-control<?= !empty($_errors['username']) ? ' is-invalid' : '' ?>"
                value="<?= htmlspecialchars($_old['username'] ?? '') ?>"
                placeholder="Nhập tên đăng nhập"
                autocomplete="username"
                autofocus
            >
            <?php if (!empty($_errors['username'])): ?>
                <span class="form-error">⚠️ <?= htmlspecialchars($_errors['username']) ?></span>
            <?php else: ?>
                <span class="form-hint" style="color:rgba(255,255,255,0.35);font-size:0.78rem;margin-top:0.25rem;display:block;">
                    Chỉ chứa chữ cái, số và dấu gạch dưới
                </span>
            <?php endif; ?>
        </div>

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
                    placeholder="Tối thiểu 8 ký tự"
                    autocomplete="new-password"
                >
                <button type="button" class="password-toggle" title="Hiện/ẩn mật khẩu">👁️</button>
            </div>
            <?php if (!empty($_errors['password'])): ?>
                <span class="form-error">⚠️ <?= htmlspecialchars($_errors['password']) ?></span>
            <?php else: ?>
                <span class="form-hint" style="color:rgba(255,255,255,0.35);font-size:0.78rem;margin-top:0.25rem;display:block;">
                    Ít nhất 8 ký tự, bao gồm chữ và số
                </span>
            <?php endif; ?>
        </div>

        <!-- Password Confirm -->
        <div class="form-group">
            <label class="form-label" for="password_confirm">🔐 Xác nhận mật khẩu</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    class="form-control<?= !empty($_errors['password_confirm']) ? ' is-invalid' : '' ?>"
                    placeholder="Nhập lại mật khẩu"
                    autocomplete="new-password"
                >
                <button type="button" class="password-toggle" title="Hiện/ẩn mật khẩu">👁️</button>
            </div>
            <?php if (!empty($_errors['password_confirm'])): ?>
                <span class="form-error">⚠️ <?= htmlspecialchars($_errors['password_confirm']) ?></span>
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
            ✨ Tạo tài khoản
        </button>
    </form>

    <!-- Login link -->
    <p class="auth-footer-text">
        Đã có tài khoản?
        <a href="<?= getDynamicUrl('/auth/login') ?>" class="auth-link">Đăng nhập</a>
    </p>
</div>
