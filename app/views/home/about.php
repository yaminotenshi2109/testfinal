<?php
/**
 * app/views/home/about.php
 * About Page — public, no auth required
 */
?>

<!-- Hero -->
<div class="hero" style="min-height: 250px; padding: 40px 24px; background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%); flex-direction: column;">
  <div class="hero-content" style="max-width: 800px; text-align: center;">
    <h1 style="font-size: 32px; line-height: 1.2;">Về chúng tôi<br><span style="background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">KTX Management System</span></h1>
    <p style="margin-top: 12px; font-size: 15px; opacity: 0.85;">Dự án quản lý ký túc xá thông minh, kiến tạo không gian sống tiện nghi, hiện đại và kết nối cho cộng đồng sinh viên.</p>
  </div>
</div>

<!-- Main Section -->
<div style="max-width: 1000px; margin: 40px auto; padding: 0 24px;">
  <div class="grid-2" style="gap: 32px; align-items: center; margin-bottom: 50px;">
    <div>
      <h2 style="font-size: 22px; font-weight: 800; color: var(--txt-primary); margin-bottom: 16px;">Tầm nhìn & Sứ mệnh</h2>
      <p style="color: var(--txt-muted); line-height: 1.6; margin-bottom: 12px; font-size: 14px;">
        Chúng tôi xây dựng giải pháp phần mềm quản lý ký túc xá KTX System nhằm giải quyết triệt để các rào cản và khó khăn trong quy trình quản lý vận hành truyền thống. 
      </p>
      <p style="color: var(--txt-muted); line-height: 1.6; font-size: 14px;">
        Nhờ ứng dụng chuyển đổi số toàn diện, ban quản lý có thể tối ưu hóa hiệu suất vận hành, giảm bớt thủ tục giấy tờ, trong khi sinh viên được trải nghiệm các dịch vụ đăng ký phòng, theo dõi hợp đồng, thanh toán hóa đơn điện nước và gửi phản ánh sự cố một cách trực quan, nhanh chóng, minh bạch.
      </p>
    </div>
    <div class="card" style="background: linear-gradient(135deg, var(--card-bg) 0%, rgba(99, 102, 241, 0.05) 100%); border: 1px solid var(--border); padding: 32px; display: flex; flex-direction: column; gap: 20px;">
      <div style="display: flex; gap: 16px;">
        <div style="font-size: 24px;">🛡️</div>
        <div>
          <h4 style="font-weight: 700; color: var(--txt-primary); font-size: 15px;">An toàn & Bảo mật</h4>
          <p style="color: var(--txt-muted); font-size: 13px; margin-top: 4px;">Dữ liệu cá nhân, lịch sử đóng tiền và thông tin hợp đồng được mã hóa bảo mật tuyệt đối.</p>
        </div>
      </div>
      <div style="display: flex; gap: 16px;">
        <div style="font-size: 24px;">⚡</div>
        <div>
          <h4 style="font-weight: 700; color: var(--txt-primary); font-size: 15px;">Tiện lợi & Nhanh chóng</h4>
          <p style="color: var(--txt-muted); font-size: 13px; margin-top: 4px;">Thực hiện mọi thủ tục đăng ký, khiếu nại, thanh toán ngay trên điện thoại hoặc máy tính.</p>
        </div>
      </div>
      <div style="display: flex; gap: 16px;">
        <div style="font-size: 24px;">🎯</div>
        <div>
          <h4 style="font-weight: 700; color: var(--txt-primary); font-size: 15px;">Trực quan & Minh bạch</h4>
          <p style="color: var(--txt-muted); font-size: 13px; margin-top: 4px;">Theo dõi chi tiết các thông số điện, nước, hóa đơn, lịch sử vi phạm rõ ràng.</p>
        </div>
      </div>
    </div>
  </div>

  <hr style="border: 0; border-top: 1px solid var(--border); margin: 40px 0;">

  <!-- Tech Stack -->
  <div style="text-align: center; margin-bottom: 36px;">
    <h2 style="font-size: 22px; font-weight: 800; color: var(--txt-primary)">Công nghệ sử dụng</h2>
    <p style="color: var(--txt-muted); font-size: 14px; margin-top: 8px;">Kiến trúc MVC vững chắc được xây dựng trên nền tảng PHP thuần cực kỳ tối ưu</p>
  </div>

  <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
    <div class="stat-card" style="--stat-color:#eab308;--stat-icon-bg:rgba(234,179,8,0.1)">
      <div class="stat-icon" style="font-size: 20px;">🐘</div>
      <div>
        <div class="stat-value" style="font-size: 18px;">PHP 8.x</div>
        <div class="stat-label">OOP / MVC Custom</div>
      </div>
    </div>
    <div class="stat-card" style="--stat-color:#3b82f6;--stat-icon-bg:rgba(59,130,246,0.1)">
      <div class="stat-icon" style="font-size: 20px;">🛢️</div>
      <div>
        <div class="stat-value" style="font-size: 18px;">MySQL</div>
        <div class="stat-label">PDO / Triggers</div>
      </div>
    </div>
    <div class="stat-card" style="--stat-color:#10b981;--stat-icon-bg:rgba(16,185,129,0.1)">
      <div class="stat-icon" style="font-size: 20px;">🎨</div>
      <div>
        <div class="stat-value" style="font-size: 18px;">CSS Glass</div>
        <div class="stat-label">Vanilla Responsive</div>
      </div>
    </div>
    <div class="stat-card" style="--stat-color:#a855f7;--stat-icon-bg:rgba(168,85,247,0.1)">
      <div class="stat-icon" style="font-size: 20px;">⚡</div>
      <div>
        <div class="stat-value" style="font-size: 18px;">Fetch API</div>
        <div class="stat-label">SPA Elements & Modals</div>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<div style="background:var(--card-bg);padding:20px 24px;text-align:center;border-top:1px solid var(--border)">
  <p style="font-size:12px;color:var(--txt-muted)">
    © <?= date('Y') ?> KTX Management System. Hân hạnh phục vụ sinh viên.
  </p>
</div>
