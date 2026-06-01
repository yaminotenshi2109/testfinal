<?php
/**
 * app/views/home/index.php
 * Landing page — public, no auth required
 */
?>

<!-- Hero -->
<div class="hero">
  <div class="hero-content">
    <h1>Hệ thống quản lý<br><span>Ký túc xá thông minh</span></h1>
    <p>Quản lý phòng ở, hợp đồng, hóa đơn và vi phạm một cách hiệu quả, minh bạch và tiện lợi cho sinh viên và ban quản lý.</p>
    <div class="hero-btns">
      <a href="/testfinal/public/auth/login" class="hero-btn-primary">🔐 Đăng nhập</a>
      <a href="#features" class="hero-btn-outline">📖 Tìm hiểu thêm</a>
    </div>
  </div>
</div>

<!-- Features -->
<div class="features" id="features">
  <div style="text-align:center;margin-bottom:36px">
    <h2 style="font-size:26px;font-weight:800;color:var(--txt-primary);letter-spacing:-.5px">Tính năng nổi bật</h2>
    <p style="color:var(--txt-muted);margin-top:8px;font-size:14px">Tất cả những gì bạn cần để quản lý ký túc xá hiện đại</p>
  </div>

  <div class="features-grid">

    <div class="feature-card">
      <div class="feature-icon" style="background:#eef2ff;color:#6366f1">🚪</div>
      <h3>Quản lý phòng ở</h3>
      <p>Theo dõi tình trạng phòng, sức chứa, trang thiết bị theo thời gian thực. Hỗ trợ nhiều loại phòng: standard, deluxe, có điều hòa.</p>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:#d1fae5;color:#10b981">🧾</div>
      <h3>Hóa đơn tự động</h3>
      <p>Tự động tính tiền phòng, điện, nước theo chỉ số thực tế hàng tháng. Hỗ trợ nhiều phương thức thanh toán: tiền mặt, chuyển khoản, VNPay, MoMo.</p>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:#fee2e2;color:#ef4444">⚠️</div>
      <h3>Theo dõi vi phạm</h3>
      <p>Hệ thống điểm vi phạm tự động. Khi vượt ngưỡng, hợp đồng chuyển sang trạng thái "đang xem xét". Sinh viên có thể khiếu nại trực tuyến.</p>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:#cffafe;color:#06b6d4">📄</div>
      <h3>Hợp đồng số</h3>
      <p>Quản lý hợp đồng thuê phòng từ A đến Z. Tạo hợp đồng khi duyệt đăng ký, theo dõi ngày hết hạn, chấm dứt hợp đồng có ghi nhận lý do.</p>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:#fef3c7;color:#f59e0b">🔧</div>
      <h3>Quản lý bảo trì</h3>
      <p>Sinh viên báo cáo sự cố phòng trực tuyến. Quản lý theo dõi và cập nhật trạng thái xử lý theo mức độ ưu tiên: thấp, trung bình, cao, khẩn cấp.</p>
    </div>

    <div class="feature-card">
      <div class="feature-icon" style="background:#ede9fe;color:#8b5cf6">🔔</div>
      <h3>Thông báo realtime</h3>
      <p>Nhận thông báo tức thì khi đăng ký được duyệt, hóa đơn mới, vi phạm ghi nhận, hay hợp đồng sắp hết hạn. Không bỏ lỡ thông tin quan trọng.</p>
    </div>

  </div>
</div>

<!-- CTA Banner -->
<div style="background:linear-gradient(135deg,#0f172a,#1e1b4b);padding:60px 24px;text-align:center;color:#fff">
  <h2 style="font-size:24px;font-weight:800;margin-bottom:12px">Sẵn sàng bắt đầu?</h2>
  <p style="color:rgba(255,255,255,.6);margin-bottom:28px;font-size:14px">Đăng nhập để truy cập hệ thống quản lý ký túc xá</p>
  <a href="/testfinal/public/auth/login" class="hero-btn-primary" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none">
    🚀 Đăng nhập ngay
  </a>
</div>

<!-- Footer -->
<div style="background:var(--card-bg);padding:20px 24px;text-align:center;border-top:1px solid var(--border)">
  <p style="font-size:12px;color:var(--txt-muted)">
    © <?= date('Y') ?> KTX Management System. Được phát triển bởi nhóm sinh viên CNTT.
  </p>
</div>
