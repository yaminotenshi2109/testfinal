<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Lỗi máy chủ | KTX System</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏠</text></svg>">
    <link rel="stylesheet" href="/testfinal/public/assets/css/app.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a0a0a 0%, #3b1010 50%, #1e1010 100%);
            margin: 0;
            padding: 2rem 1rem;
            box-sizing: border-box;
        }

        .error-page {
            text-align: center;
            max-width: 540px;
            width: 100%;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .error-emoji {
            font-size: 5rem;
            line-height: 1;
            margin-bottom: 1.5rem;
            display: block;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50%       { transform: scale(1.08); opacity: 0.8; }
        }

        .error-code {
            font-size: clamp(6rem, 18vw, 9rem);
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #991b1b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 0.75rem;
            letter-spacing: -0.04em;
            filter: drop-shadow(0 4px 20px rgba(220,38,38,0.5));
        }

        .error-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 0.75rem;
        }

        .error-message {
            font-size: 1rem;
            color: rgba(255,255,255,0.55);
            margin: 0 0 2rem;
            line-height: 1.6;
        }

        .error-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .error-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.7rem 1.5rem;
            border-radius: 0.6rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: transform 0.15s, box-shadow 0.2s, opacity 0.2s;
        }

        .error-btn-primary {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: #fff;
            box-shadow: 0 4px 15px rgba(220,38,38,0.4);
        }

        .error-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220,38,38,0.55);
            opacity: 0.95;
        }

        .error-btn-outline {
            background: rgba(255,255,255,0.07);
            color: rgba(255,255,255,0.75);
            border: 1px solid rgba(255,255,255,0.18);
            backdrop-filter: blur(8px);
        }

        .error-btn-outline:hover {
            background: rgba(255,255,255,0.12);
            transform: translateY(-2px);
            color: #fff;
        }

        .error-content {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 1.5rem;
            padding: 3rem 2rem;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
        }

        .error-divider {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #ef4444, #b91c1c);
            border-radius: 2px;
            margin: 1.25rem auto;
        }

        .error-detail {
            display: inline-block;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.78rem;
            color: rgba(252,165,165,0.7);
            margin-bottom: 1.5rem;
            font-family: monospace;
            letter-spacing: 0.02em;
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-content">
            <span class="error-emoji">💥</span>
            <h1 class="error-code">500</h1>
            <div class="error-divider"></div>
            <h2 class="error-title">Lỗi máy chủ nội bộ</h2>
            <?php if (!empty($errorMessage)): ?>
                <div class="error-detail"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>
            <p class="error-message">
                Đã có lỗi xảy ra từ phía máy chủ. Chúng tôi đang khắc phục sự cố này.
                Vui lòng thử lại sau hoặc liên hệ với quản trị viên hệ thống.
            </p>
            <div class="error-actions">
                <a href="javascript:location.reload()" class="error-btn error-btn-outline">
                    🔄 Tải lại trang
                </a>
                <a href="/testfinal/public/" class="error-btn error-btn-primary">
                    🏠 Trang chủ
                </a>
            </div>
        </div>
    </div>
</body>
</html>
