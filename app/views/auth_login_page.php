<?php
/**
 * app/views/auth/login.php
 * ─────────────────────────────────────────────────────────────
 *  Login page for KTX system
 *  Admin and Student both use this page
 * ─────────────────────────────────────────────────────────────
 */
?><!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — KTX Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #185FA5 0%, #154FA0 100%);
        }

        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            font-size: 40px;
            color: #185FA5;
            margin-bottom: 10px;
        }

        .login-title {
            font-size: 24px;
            font-weight: 600;
            color: #2C2C2A;
            margin-bottom: 4px;
        }

        .login-subtitle {
            font-size: 13px;
            color: #888780;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #3d3d3a;
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 0.5px solid #D3D1C7;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: #185FA5;
            box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.1);
        }

        .form-input::placeholder {
            color: #D3D1C7;
        }

        .form-error {
            margin-top: 4px;
            font-size: 12px;
            color: #A32D2D;
            display: none;
        }

        .form-input.error {
            border-color: #A32D2D;
        }

        .form-input.error ~ .form-error {
            display: block;
        }

        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #3d3d3a;
            margin-bottom: 1.5rem;
        }

        .form-checkbox input[type="checkbox"] {
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: #185FA5;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: #154FA0;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .btn-login:disabled {
            background: #D3D1C7;
            cursor: not-allowed;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 0.5px solid #D3D1C7;
            font-size: 13px;
            color: #888780;
        }

        .login-footer a {
            color: #185FA5;
            text-decoration: none;
            font-weight: 500;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 13px;
            border-left: 4px solid;
        }

        .alert-danger {
            background: #FCEBEB;
            border-left-color: #A32D2D;
            color: #501313;
        }

        .alert-info {
            background: #E6F1FB;
            border-left-color: #378ADD;
            color: #042C53;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 1.5rem;
            }

            .login-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">🏢</div>
                <h1 class="login-title">KTX Management</h1>
                <p class="login-subtitle">Hệ thống quản lý ký túc xá</p>
            </div>

            <?php if (!empty($_errors ?? [])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_errors['login'] ?? 'Email hoặc mật khẩu không đúng') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_old ?? [])): ?>
                <div class="alert alert-info">
                    Vui lòng kiểm tra thông tin đăng nhập
                </div>
            <?php endif; ?>

            <form id="loginForm" onsubmit="handleLogin(event)">
                <input type="hidden" name="_csrf_token" value="<?= $_csrfToken ?? '' ?>">

                <div class="form-group">
                    <label class="form-label" for="email">Email hoặc tên đăng nhập</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="your@email.com"
                        value="<?= htmlspecialchars($_old['email'] ?? '') ?>"
                        required
                    >
                    <div class="form-error" id="emailError"></div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Mật khẩu</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                    >
                    <div class="form-error" id="passwordError"></div>
                </div>

                <div class="form-checkbox">
                    <input type="checkbox" id="rememberMe" name="remember_me">
                    <label for="rememberMe">Nhớ tôi</label>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    Đăng nhập
                </button>
            </form>

            <div class="login-footer">
                Bạn chưa có tài khoản? 
                <a href="/register">Đăng ký tại đây</a>
            </div>

            <div class="login-footer" style="margin-top: 1rem; border-top: none;">
                <small>
                    Demo: 
                    <strong>admin@ktx.edu.vn / Admin@123</strong>
                </small>
            </div>
        </div>
    </div>

    <script>
        function handleLogin(e) {
            e.preventDefault();
            
            const form = document.getElementById('loginForm');
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const submitBtn = document.getElementById('submitBtn');

            // Clear previous errors
            document.querySelectorAll('.form-input').forEach(input => {
                input.classList.remove('error');
            });
            document.querySelectorAll('.form-error').forEach(el => {
                el.textContent = '';
            });

            // Validate
            let hasError = false;
            if (!email) {
                document.getElementById('email').classList.add('error');
                document.getElementById('emailError').textContent = 'Email không được để trống';
                hasError = true;
            } else if (!email.includes('@') && !email.match(/^[a-z0-9_]+$/i)) {
                document.getElementById('email').classList.add('error');
                document.getElementById('emailError').textContent = 'Email không hợp lệ';
                hasError = true;
            }

            if (!password) {
                document.getElementById('password').classList.add('error');
                document.getElementById('passwordError').textContent = 'Mật khẩu không được để trống';
                hasError = true;
            }

            if (hasError) return;

            // Submit
            submitBtn.disabled = true;
            submitBtn.textContent = 'Đang đăng nhập...';

            fetch('/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email,
                    password,
                    remember_me: document.getElementById('rememberMe').checked,
                    _csrf_token: form._csrf_token.value
                })
            })
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    // Redirect to dashboard
                    window.location.href = json.redirect || '/admin/dashboard';
                } else {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Đăng nhập';
                    
                    // Show error
                    if (json.errors) {
                        Object.entries(json.errors).forEach(([field, msgs]) => {
                            const input = document.getElementById(field);
                            if (input) {
                                input.classList.add('error');
                                document.getElementById(field + 'Error').textContent = msgs[0];
                            }
                        });
                    } else {
                        alert(json.message || 'Đăng nhập thất bại');
                    }
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Đăng nhập';
                alert('Lỗi mạng: ' + err.message);
            });
        }

        // Auto-focus email field
        document.getElementById('email').focus();
    </script>
</body>
</html>
