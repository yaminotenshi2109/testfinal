<?php
/**
 * app/views/admin/invoices/show.php
 * Chi tiết hóa đơn dành cho Admin
 */
?>

<div class="container-fluid" style="padding: 24px;">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="/testfinal/public/admin/invoices" class="btn btn-secondary" style="margin-bottom: 12px; display: inline-flex; align-items: center; gap: 8px;">
                ⬅️ Quay lại danh sách
            </a>
            <h1 style="margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff;">Chi tiết Hóa đơn #<?= $invoice['id'] ?></h1>
            <p style="color: var(--txt-muted); margin: 4px 0 0 0;">Kỳ thanh toán: Tháng <?= $invoice['month'] ?>/<?= $invoice['year'] ?></p>
        </div>

        <div style="display: flex; gap: 12px;">
            <a href="/testfinal/public/api/invoices/<?= $invoice['id'] ?>/pdf" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
                🖨️ In / Tải PDF
            </a>
            <?php if ($invoice['status'] === 'unpaid' || $invoice['status'] === 'overdue'): ?>
                <button type="button" class="btn btn-success" onclick="openPaymentModal()" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
                    💰 Xác nhận thanh toán
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row" style="display: flex; flex-wrap: wrap; gap: 24px;">
        <!-- Left: Details & Breakdown -->
        <div style="flex: 2; min-width: 320px;">
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header" style="border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 600; color: #fff;">Thông tin chung</h3>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div>
                        <span style="display: block; font-size: 0.85rem; color: var(--txt-muted); margin-bottom: 4px;">Sinh viên</span>
                        <strong style="font-size: 1.05rem; color: #fff;"><?= htmlspecialchars($invoice['full_name']) ?></strong>
                        <span style="display: block; font-size: 0.85rem; color: var(--txt-muted); margin-top: 2px;">MSSV: <?= htmlspecialchars($invoice['student_code']) ?></span>
                    </div>

                    <div>
                        <span style="display: block; font-size: 0.85rem; color: var(--txt-muted); margin-bottom: 4px;">Phòng & Tòa</span>
                        <strong style="font-size: 1.05rem; color: #fff;">Phòng <?= htmlspecialchars($invoice['room_number']) ?></strong>
                        <span style="display: block; font-size: 0.85rem; color: var(--txt-muted); margin-top: 2px;">Tòa: <?= htmlspecialchars($invoice['building_name']) ?></span>
                    </div>

                    <div>
                        <span style="display: block; font-size: 0.85rem; color: var(--txt-muted); margin-bottom: 4px;">Hạn thanh toán</span>
                        <strong style="font-size: 1.05rem; color: #fff;"><?= date('d/m/Y', strtotime($invoice['due_date'])) ?></strong>
                    </div>

                    <div>
                        <span style="display: block; font-size: 0.85rem; color: var(--txt-muted); margin-bottom: 4px;">Trạng thái</span>
                        <?php
                        $statusCls = match($invoice['status']) {
                            'paid' => 'badge-success',
                            'unpaid' => 'badge-warning',
                            'overdue' => 'badge-danger',
                            default => 'badge-secondary',
                        };
                        $statusText = match($invoice['status']) {
                            'paid' => 'Đã thanh toán',
                            'unpaid' => 'Chưa thanh toán',
                            'overdue' => 'Quá hạn',
                            default => 'Đã hủy',
                        };
                        ?>
                        <span class="badge <?= $statusCls ?>" style="font-size: 0.9rem; padding: 6px 12px;"><?= $statusText ?></span>
                    </div>
                </div>
            </div>

            <!-- Breakdown -->
            <div class="card">
                <div class="card-header" style="border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 600; color: #fff;">Chi tiết các khoản phí</h3>
                </div>

                <div class="table-responsive">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); text-align: left;">
                                <th style="padding: 12px; color: var(--txt-muted); font-weight: 500;">Khoản phí</th>
                                <th style="padding: 12px; color: var(--txt-muted); font-weight: 500; text-align: right;">Thành tiền (VND)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 16px 12px;">
                                    <strong style="color: #fff; display: block;">🏢 Tiền thuê phòng</strong>
                                    <span style="font-size: 0.85rem; color: var(--txt-muted);">Giá thuê phòng cơ bản hàng tháng</span>
                                </td>
                                <td style="padding: 16px 12px; text-align: right; color: #fff; font-weight: 600; font-size: 1.05rem;">
                                    <?= number_format((float)$invoice['base_rent'], 0, ',', '.') ?>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 16px 12px;">
                                    <strong style="color: #fff; display: block;">⚡ Tiền điện sinh hoạt</strong>
                                    <span style="font-size: 0.85rem; color: var(--txt-muted);">Dựa trên chỉ số tiêu thụ của phòng</span>
                                </td>
                                <td style="padding: 16px 12px; text-align: right; color: #fff; font-weight: 600; font-size: 1.05rem;">
                                    <?= number_format((float)$invoice['electricity_fee'], 0, ',', '.') ?>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 16px 12px;">
                                    <strong style="color: #fff; display: block;">💧 Tiền nước sạch</strong>
                                    <span style="font-size: 0.85rem; color: var(--txt-muted);">Dựa trên khối lượng nước tiêu thụ</span>
                                </td>
                                <td style="padding: 16px 12px; text-align: right; color: #fff; font-weight: 600; font-size: 1.05rem;">
                                    <?= number_format((float)$invoice['water_fee'], 0, ',', '.') ?>
                                </td>
                            </tr>
                            <?php if ((float)$invoice['ac_fee'] > 0): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 16px 12px;">
                                    <strong style="color: #fff; display: block;">❄️ Phụ phí điều hòa</strong>
                                    <span style="font-size: 0.85rem; color: var(--txt-muted);">Phí dịch vụ phòng điều hòa</span>
                                </td>
                                <td style="padding: 16px 12px; text-align: right; color: #fff; font-weight: 600; font-size: 1.05rem;">
                                    <?= number_format((float)$invoice['ac_fee'], 0, ',', '.') ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php if ((float)$invoice['other_fee'] > 0): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 16px 12px;">
                                    <strong style="color: #fff; display: block;">⚙️ Chi phí dịch vụ khác</strong>
                                    <span style="font-size: 0.85rem; color: var(--txt-muted);">Phí vệ sinh, mạng internet hoặc phụ thu</span>
                                </td>
                                <td style="padding: 16px 12px; text-align: right; color: #fff; font-weight: 600; font-size: 1.05rem;">
                                    <?= number_format((float)$invoice['other_fee'], 0, ',', '.') ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr style="background: rgba(99,102,241,0.08);">
                                <td style="padding: 20px 12px;">
                                    <strong style="color: var(--brand); font-size: 1.15rem; display: block;">💰 TỔNG CỘNG THÀNH TIỀN</strong>
                                    <span style="font-size: 0.85rem; color: var(--txt-muted);">Tổng tất cả các khoản chi phí học kỳ này</span>
                                </td>
                                <td style="padding: 20px 12px; text-align: right; color: var(--brand); font-weight: 700; font-size: 1.4rem;">
                                    <?= number_format((float)$invoice['total_amount'], 0, ',', '.') ?> VND
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Payment history/meta info -->
        <div style="flex: 1; min-width: 280px; max-width: 400px;">
            <div class="card" style="margin-bottom: 24px;">
                <div class="card-header" style="border-bottom: 1px solid var(--border); padding-bottom: 16px; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 600; color: #fff;">Thông tin thanh toán</h3>
                </div>

                <?php if ($invoice['status'] === 'paid'): ?>
                    <div style="text-align: center; padding: 16px 0;">
                        <span style="font-size: 4rem; display: block; margin-bottom: 12px;">✅</span>
                        <h4 style="color: #6ee7b7; font-size: 1.2rem; margin: 0 0 8px 0; font-weight: 600;">Đã thanh toán</h4>
                        <p style="color: var(--txt-muted); font-size: 0.85rem; margin: 0 0 16px 0;">Hóa đơn đã được thanh toán đầy đủ.</p>
                    </div>
                    
                    <div style="border-top: 1px solid rgba(255,255,255,0.05); padding-top: 16px; display: flex; flex-direction: column; gap: 12px;">
                        <div>
                            <span style="display: block; font-size: 0.8rem; color: var(--txt-muted);">Thời gian nộp</span>
                            <strong style="color: #fff; font-size: 0.95rem;"><?= date('d/m/Y H:i:s', strtotime($invoice['paid_at'])) ?></strong>
                        </div>
                        <div>
                            <span style="display: block; font-size: 0.8rem; color: var(--txt-muted);">Phương thức</span>
                            <strong style="color: #fff; font-size: 0.95rem;">
                                <?= match($invoice['payment_method']) {
                                    'cash' => '💵 Tiền mặt',
                                    'transfer' => '🏦 Chuyển khoản ngân hàng',
                                    'momo' => '📱 Ví Momo',
                                    default => 'Điện tử VNPAY',
                                } ?>
                            </strong>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 16px 0;">
                        <span style="font-size: 4rem; display: block; margin-bottom: 12px;">⏳</span>
                        <h4 style="color: #fca5a5; font-size: 1.2rem; margin: 0 0 8px 0; font-weight: 600;">Chờ thanh toán</h4>
                        <p style="color: var(--txt-muted); font-size: 0.85rem; margin: 0;">Hóa đơn đang chờ thanh toán của sinh viên hoặc xác nhận của bộ phận quản lý tài vụ.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Note/Guide card -->
            <div class="card" style="background: rgba(255,255,255,0.02);">
                <div class="card-header" style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px;">
                    <h4 style="margin: 0; font-size: 1rem; font-weight: 600; color: #fff;">💡 Hướng dẫn</h4>
                </div>
                <p style="font-size: 0.85rem; color: var(--txt-muted); line-height: 1.6; margin: 0;">
                    • Nút <strong>In / Tải PDF</strong> sẽ tải xuống file PDF chuyên nghiệp để lưu trữ hoặc in ấn.<br><br>
                    • Nếu thanh toán trực tiếp bằng tiền mặt, admin nhấn <strong>Xác nhận thanh toán</strong> để hoàn tất hóa đơn ngay lập tức.<br><br>
                    • Đảm bảo chỉ số điện nước đã được đo đạc chính xác trước khi xuất hóa đơn.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- CSRF Token for Ajax -->
<input type="hidden" id="csrf_token_val" value="<?= htmlspecialchars($_csrfToken ?? '') ?>">

<!-- Payment Modal -->
<div class="modal-backdrop" id="paymentModalBackdrop" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 440px; margin: 16px; padding: 24px; position: relative;">
        <h3 style="margin-top: 0; color: #fff; font-size: 1.25rem; font-weight: 600;">💰 Xác nhận Thanh toán Hóa đơn</h3>
        <p style="color: var(--txt-muted); font-size: 0.85rem; margin-bottom: 20px;">Hành động này sẽ cập nhật trạng thái hóa đơn sang đã thanh toán.</p>
        
        <form id="paymentConfirmForm" onsubmit="submitPayment(event)">
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" for="payment_method">Phương thức thanh toán</label>
                <select id="payment_method" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: #fff;">
                    <option value="cash" style="background: #1a1625;">💵 Tiền mặt (Trực tiếp)</option>
                    <option value="transfer" style="background: #1a1625;">🏦 Chuyển khoản ngân hàng</option>
                    <option value="momo" style="background: #1a1625;">📱 Ví điện tử Momo</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" for="transaction_id">Mã giao dịch (Nếu có)</label>
                <input type="text" id="transaction_id" class="form-control" placeholder="Ví dụ: FT1234567890" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: #fff;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Hủy bỏ</button>
                <button type="submit" class="btn btn-success">✅ Xác nhận nộp</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal() {
    document.getElementById('paymentModalBackdrop').style.display = 'flex';
}

function closePaymentModal() {
    document.getElementById('paymentModalBackdrop').style.display = 'none';
}

function submitPayment(e) {
    e.preventDefault();
    
    var method = document.getElementById('payment_method').value;
    var txId = document.getElementById('transaction_id').value;
    var csrf = document.getElementById('csrf_token_val').value;
    
    var data = {
        payment_method: method,
        transaction_id: txId,
        _csrf_token: csrf
    };
    
    fetch('/testfinal/public/api/invoices/<?= $invoice['id'] ?>/pay', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            alert('Xác nhận thanh toán thành công!');
            window.location.reload();
        } else {
            alert('Lỗi: ' + res.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Đã xảy ra lỗi khi gửi yêu cầu.');
    });
}
</script>
