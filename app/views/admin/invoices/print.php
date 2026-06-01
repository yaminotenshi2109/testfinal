<?php
/**
 * app/views/admin/invoices/print.php
 * Bản in hóa đơn KTX chuyên nghiệp (A4 layout, high contrast)
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Hóa đơn #<?= $invoice['id'] ?> - KTX System</title>
    <style>
        @media print {
            body {
                background: #fff;
                color: #000;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .invoice-box {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            margin: 0;
            padding: 40px 10px;
            font-size: 14px;
            line-height: 1.5;
        }
        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-primary {
            background-color: #4f46e5;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #4338ca;
        }
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        .invoice-box {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo-section h2 {
            margin: 0 0 4px 0;
            font-size: 24px;
            font-weight: 800;
            color: #4f46e5;
            letter-spacing: -0.02em;
        }
        .logo-section p {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
        }
        .title-section {
            text-align: right;
        }
        .title-section h1 {
            margin: 0 0 6px 0;
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }
        .title-section p {
            margin: 0;
            color: #4b5563;
            font-size: 13px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-bottom: 40px;
        }
        .details-block h3 {
            margin: 0 0 12px 0;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4f46e5;
        }
        .details-block p {
            margin: 4px 0;
            color: #374151;
        }
        .details-block strong {
            color: #111827;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .table th {
            background-color: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            padding: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4b5563;
            text-align: left;
        }
        .table td {
            padding: 16px 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .table .desc {
            color: #6b7280;
            font-size: 12px;
            margin-top: 2px;
            display: block;
        }
        .total-row {
            background-color: #f9fafb;
            font-weight: 700;
            font-size: 15px;
            border-top: 2px solid #e5e7eb;
        }
        .total-row td {
            padding: 20px 12px !important;
        }
        .payment-info {
            border-top: 1px solid #e5e7eb;
            padding-top: 30px;
            margin-bottom: 40px;
        }
        .payment-info h3 {
            margin: 0 0 12px 0;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }
        .payment-info p {
            margin: 4px 0;
            color: #4b5563;
            line-height: 1.6;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 60px;
            border-top: 1px dashed #d1d5db;
            padding-top: 20px;
        }
        .footer-note {
            max-width: 400px;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.5;
        }
        .signature-block {
            text-align: center;
            width: 250px;
        }
        .signature-block p {
            margin: 0 0 50px 0;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }
        .signature-block span {
            display: block;
            border-top: 1px solid #9ca3af;
            padding-top: 6px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>

<div class="no-print-bar no-print">
    <div>
        <a href="/testfinal/public/admin/invoices/<?= $invoice['id'] ?>" class="btn btn-secondary">⬅️ Trở lại chi tiết</a>
    </div>
    <div style="display: flex; gap: 12px;">
        <button class="btn btn-primary" onclick="window.print()">🖨️ In ngay (Print)</button>
    </div>
</div>

<div class="invoice-box">
    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <h2>🏠 KTX MANAGEMENT</h2>
            <p>Hệ thống Quản lý Ký túc xá Đại học</p>
        </div>
        <div class="title-section">
            <h1>HÓA ĐƠN TIỀN PHÒNG</h1>
            <p>Mã hóa đơn: <strong>#<?= $invoice['id'] ?></strong></p>
            <p>Kỳ thanh toán: Tháng <?= $invoice['month'] ?>/<?= $invoice['year'] ?></p>
        </div>
    </div>

    <!-- Details -->
    <div class="details-grid">
        <div class="details-block">
            <h3>Thông tin người nộp</h3>
            <p>Sinh viên: <strong><?= htmlspecialchars($invoice['full_name']) ?></strong></p>
            <p>Mã số sinh viên: <?= htmlspecialchars($invoice['student_code']) ?></p>
            <p>Phòng ở: Phòng <?= htmlspecialchars($invoice['room_number']) ?> - Tòa <?= htmlspecialchars($invoice['building_name']) ?></p>
        </div>
        <div class="details-block" style="text-align: right;">
            <h3>Thông tin hóa đơn</h3>
            <p>Ngày xuất: <?= date('d/m/Y', strtotime($invoice['created_at'])) ?></p>
            <p>Hạn thanh toán: <strong><?= date('d/m/Y', strtotime($invoice['due_date'])) ?></strong></p>
            <p>Trạng thái: 
                <strong>
                    <?= $invoice['status'] === 'paid' ? 'ĐÃ THANH TOÁN' : 'CHƯA THANH TOÁN' ?>
                </strong>
            </p>
        </div>
    </div>

    <!-- Table -->
    <table class="table">
        <thead>
            <tr>
                <th>Khoản phí</th>
                <th style="text-align: right;">Thành tiền (VND)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>🏢 Tiền thuê phòng cơ bản</strong>
                    <span class="desc">Phí phòng ở tiêu chuẩn tính theo tháng</span>
                </td>
                <td style="text-align: right; font-weight: 600;">
                    <?= number_format((float)$invoice['base_rent'], 0, ',', '.') ?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>⚡ Chỉ số điện sinh hoạt</strong>
                    <span class="desc">Phí sử dụng điện thực tế theo chỉ số công tơ</span>
                </td>
                <td style="text-align: right; font-weight: 600;">
                    <?= number_format((float)$invoice['electricity_fee'], 0, ',', '.') ?>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>💧 Chỉ số nước sạch sinh hoạt</strong>
                    <span class="desc">Phí sử dụng nước sạch theo m³ sử dụng thực tế</span>
                </td>
                <td style="text-align: right; font-weight: 600;">
                    <?= number_format((float)$invoice['water_fee'], 0, ',', '.') ?>
                </td>
            </tr>
            <?php if ((float)$invoice['ac_fee'] > 0): ?>
            <tr>
                <td>
                    <strong>❄️ Phụ phí điều hòa</strong>
                    <span class="desc">Dịch vụ bổ sung cho phòng có trang bị máy lạnh</span>
                </td>
                <td style="text-align: right; font-weight: 600;">
                    <?= number_format((float)$invoice['ac_fee'], 0, ',', '.') ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$invoice['other_fee'] > 0): ?>
            <tr>
                <td>
                    <strong>⚙️ Phí dịch vụ khác</strong>
                    <span class="desc">Phụ thu vệ sinh, internet và an ninh</span>
                </td>
                <td style="text-align: right; font-weight: 600;">
                    <?= number_format((float)$invoice['other_fee'], 0, ',', '.') ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td style="color: #4f46e5;">TỔNG CỘNG THÀNH TIỀN</td>
                <td style="text-align: right; color: #4f46e5; font-size: 18px;">
                    <?= number_format((float)$invoice['total_amount'], 0, ',', '.') ?> VND
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Payment info -->
    <div class="payment-info">
        <h3>🏦 PHƯƠNG THỨC THANH TOÁN</h3>
        <p>
            <strong>1. Chuyển khoản ngân hàng:</strong><br>
            • Số tài khoản: <strong>1234567890</strong> (Ngân hàng VietcomBank)<br>
            • Nội dung chuyển khoản: <strong>Hóa đơn #<?= $invoice['id'] ?> - <?= $invoice['student_code'] ?></strong>
        </p>
        <p style="margin-top: 12px;">
            <strong>2. Trực tiếp tiền mặt:</strong><br>
            • Nộp trực tiếp tại Văn phòng Ban quản lý Ký túc xá (Phòng A101, Tầng 1, Tòa A).
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-note">
            * Lưu ý: Vui lòng thanh toán hóa đơn đúng hạn để tránh các phụ phí phát sinh trễ hạn (50.000đ/ngày). Mọi phản hồi thắc mắc về chỉ số điện nước xin liên hệ Ban quản lý trước thời hạn nộp.<br><br>
            <em>Cảm ơn sự hợp tác của sinh viên!</em>
        </div>
        
        <div class="signature-block">
            <p>Bộ phận Tài vụ Ký túc xá</p>
            <span>Ký tên & xác nhận</span>
        </div>
    </div>
</div>

<script>
    // Tự động mở cửa sổ in của trình duyệt khi mở trang
    window.addEventListener('DOMContentLoaded', (event) => {
        // Chỉ in tự động nếu trong môi trường print preview
        if (window.location.search.indexOf('autoprint') !== -1) {
            window.print();
        }
    });
</script>
</body>
</html>
