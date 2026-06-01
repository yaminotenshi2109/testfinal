<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Đăng nhập') ?> — KTX System</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
    <link rel="stylesheet" href="<?= getDynamicUrl('/assets/css/app.css') ?>">
    <style>
        .auth-layout {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
        }
        .auth-layout::before {
            content: '';
            position: absolute;
            top: -40%;
            left: -20%;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .auth-layout::after {
            content: '';
            position: absolute;
            bottom: -30%;
            right: -10%;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(168,85,247,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .auth-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.1);
            position: relative;
            z-index: 1;
            color: #e2e8f0;
        }
        .auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-logo-icon {
            font-size: 3rem;
            line-height: 1;
            margin-bottom: 0.75rem;
            display: block;
            filter: drop-shadow(0 4px 12px rgba(99,102,241,0.5));
        }
        .auth-logo h1 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 0.25rem;
            letter-spacing: -0.02em;
        }
        .auth-logo p {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            margin: 0;
        }
        .auth-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #fff;
            margin: 0 0 0.25rem;
        }
        .auth-subtitle {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.5);
            margin: 0 0 1.5rem;
        }
        .auth-divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin: 1.5rem 0;
        }
        .auth-card .form-label {
            color: rgba(255,255,255,0.75);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.4rem;
            display: block;
        }
        .auth-card .form-control {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.15);
            color: #fff;
            border-radius: 0.6rem;
            padding: 0.65rem 0.9rem;
            width: 100%;
            box-sizing: border-box;
            font-size: 0.9rem;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }
        .auth-card .form-control:focus {
            outline: none;
            border-color: rgba(99,102,241,0.7);
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.2);
        }
        .auth-card .form-control::placeholder {
            color: rgba(255,255,255,0.3);
        }
        .auth-card .form-error {
            color: #fc8181;
            font-size: 0.8rem;
            margin-top: 0.3rem;
            display: block;
        }
        .auth-card .form-group {
            margin-bottom: 1.1rem;
        }
        .auth-card .btn-primary {
            width: 100%;
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 0.6rem;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border: none;
            color: #fff;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(99,102,241,0.4);
            margin-top: 0.5rem;
        }
        .auth-card .btn-primary:hover {
            opacity: 0.92;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99,102,241,0.5);
        }
        .auth-card .btn-primary:active {
            transform: translateY(0);
        }
        .auth-link {
            color: #a5b4fc;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .auth-link:hover {
            color: #c7d2fe;
            text-decoration: underline;
        }
        .auth-footer-text {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.45);
        }
        .auth-card .alert {
            border-radius: 0.6rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .auth-card .alert-success {
            background: rgba(52,211,153,0.15);
            border: 1px solid rgba(52,211,153,0.3);
            color: #6ee7b7;
        }
        .auth-card .alert-danger {
            background: rgba(248,113,113,0.15);
            border: 1px solid rgba(248,113,113,0.3);
            color: #fca5a5;
        }
        .auth-card .alert-info {
            background: rgba(96,165,250,0.15);
            border: 1px solid rgba(96,165,250,0.3);
            color: #93c5fd;
        }
        .auth-card .alert-warning {
            background: rgba(251,191,36,0.15);
            border: 1px solid rgba(251,191,36,0.3);
            color: #fde68a;
        }
        .password-wrapper {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.4);
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
            line-height: 1;
            transition: color 0.2s;
        }
        .password-toggle:hover {
            color: rgba(255,255,255,0.7);
        }
    </style>
</head>
<body>
<div class="auth-layout">
    <?= $content ?>
</div>
<script src="<?= getDynamicUrl('/assets/js/app.js') ?>"></script>
<script>
// Password visibility toggle
document.querySelectorAll('.password-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var input = this.closest('.password-wrapper').querySelector('input');
        if (input.type === 'password') {
            input.type = 'text';
            this.textContent = '🙈';
        } else {
            input.type = 'password';
            this.textContent = '👁️';
        }
    });
});
</script>
</body>
</html>
