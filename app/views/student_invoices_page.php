<?php
/**
 * app/views/student/invoices/index.php
 * ─────────────────────────────────────────────────────────────
 *  Student invoice management
 *  Shows personal invoices with payment status
 * ─────────────────────────────────────────────────────────────
 */
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Hóa đơn của tôi</h1>
        <p class="page-subtitle">Quản lý các hóa đơn thuê phòng</p>
    </div>
    <div style="text-align: right;">
        <div style="font-size: 13px; color: var(--color-text-muted);">Tổng nợ</div>
        <div style="font-size: 24px; font-weight: 600; color: var(--color-danger);">
            <?= number_format($totalUnpaid ?? 0, 0) ?> VND
        </div>
    </div>
</div>

<!-- Status Overview -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="metric-card">
        <span class="metric-label">Đã thanh toán</span>
        <div class="metric-value"><?= $paidCount ?? 0 ?></div>
        <span style="font-size: 12px; color: #3B6D11;">
            <i class="ti ti-check"></i> Đầy đủ
        </span>
    </div>

    <div class="metric-card">
        <span class="metric-label">Chưa thanh toán</span>
        <div class="metric-value"><?= $unpaidCount ?? 0 ?></div>
        <span style="font-size: 12px; color: #A32D2D;">
            <i class="ti ti-alert-triangle"></i> Quá hạn
        </span>
    </div>

    <div class="metric-card">
        <span class="metric-label">Kỳ hiện tại</span>
        <div class="metric-value"><?= date('m/Y') ?></div>
        <span style="font-size: 12px; color: #BA7517;">
            <i class="ti ti-clock"></i> Hạn nộp: <?= date('d') ?>
        </span>
    </div>
</div>

<!-- Filter & Export -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
        <div style="display: flex; gap: 12px; flex: 1;">
            <select style="padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;" id="statusFilter" onchange="filterInvoices()">
                <option value="">Tất cả trạng thái</option>
                <option value="unpaid">Chưa thanh toán</option>
                <option value="paid">Đã thanh toán</option>
                <option value="overdue">Quá hạn</option>
            </select>
            <select style="padding: 0.5rem 0.75rem; border: 0.5px solid var(--color-border); border-radius: 4px;" id="yearFilter" onchange="filterInvoices()">
                <option value="">Tất cả năm</option>
                <option value="2025">2025</option>
                <option value="2024">2024</option>
            </select>
        </div>
        <button class="btn btn-sm" onclick="exportInvoices()">
            <i class="ti ti-download"></i>
            <span>Xuất Excel</span>
        </button>
    </div>
</div>

<!-- Invoice Table -->
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Kỳ</th>
                <th>Phòng</th>
                <th>Tiền phòng</th>
                <th>Điện/Nước</th>
                <th>Tổng cộng</th>
                <th>Hạn nộp</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($invoices ?? []) as $invoice): ?>
                <tr>
                    <td style="font-weight: 500;">
                        Tháng <?= $invoice['month'] ?>/<?= $invoice['year'] ?>
                    </td>
                    <td><?= htmlspecialchars($invoice['room_number']) ?></td>
                    <td><?= number_format($invoice['base_rent'], 0) ?> VND</td>
                    <td>
                        <?= number_format(
                            ($invoice['electricity_fee'] ?? 0) + 
                            ($invoice['water_fee'] ?? 0) + 
                            ($invoice['ac_fee'] ?? 0), 0
                        ) ?> VND
                    </td>
                    <td style="font-weight: 600; font-size: 14px;">
                        <?= number_format($invoice['total_amount'], 0) ?> VND
                    </td>
                    <td>
                        <small style="color: var(--color-text-muted);">
                            <?= date('d/m/Y', strtotime($invoice['due_date'])) ?>
                        </small>
                    </td>
                    <td>
                        <?php 
                            $status = $invoice['status'];
                            $statusClass = $status === 'paid' ? 'status-active' : 
                                          ($status === 'unpaid' ? 'status-error' : 'status-pending');
                            $statusText = $status === 'paid' ? 'Đã thanh toán' : 
                                         ($status === 'unpaid' ? 'Chưa thanh toán' : 'Quá hạn');
                        ?>
                        <span class="table-status <?= $statusClass ?>">
                            <?= $statusText ?>
                        </span>
                    </td>
                    <td>
                        <a href="/student/invoices/<?= $invoice['id'] ?>/pdf" class="btn btn-sm" title="Tải PDF">
                            <i class="ti ti-file-pdf"></i>
                        </a>
                        <?php if ($status === 'unpaid'): ?>
                            <button class="btn btn-sm btn-success" onclick="payInvoice(<?= $invoice['id'] ?>)" title="Thanh toán">
                                <i class="ti ti-credit-card"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (empty($invoices)): ?>
        <div style="text-align: center; padding: 2rem; color: var(--color-text-muted);">
            <i class="ti ti-inbox" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
            <p>Bạn chưa có hóa đơn nào</p>
        </div>
    <?php endif; ?>
</div>

<!-- Payment Methods Modal -->
<div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 90%; max-width: 500px;">
        <div class="card-header">
            <h3 class="card-title">Chọn phương thức thanh toán</h3>
        </div>
        
        <div style="padding: 1.5rem;">
            <div id="invoiceInfo" style="background: #F8F7F2; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Kỳ:</span>
                    <strong id="invoicePeriod">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 600;">
                    <span>Tổng thanh toán:</span>
                    <span id="invoiceAmount">0 VND</span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <button class="btn" style="text-align: left; padding: 1rem; border: 1px solid var(--color-border); border-radius: 4px; background: white;" onclick="selectPaymentMethod('bank')">
                    <div style="display: flex; align-items: center; gap: 12px; width: 100%;">
                        <i class="ti ti-building-bank" style="font-size: 24px; color: #185FA5;"></i>
                        <div style="flex: 1;">
                            <div style="font-weight: 500; font-size: 14px;">Chuyển khoản ngân hàng</div>
                            <div style="font-size: 12px; color: var(--color-text-muted);">Chuyển tiền vào tài khoản nhà trường</div>
                        </div>
                        <i class="ti ti-arrow-right"></i>
                    </div>
                </button>

                <button class="btn" style="text-align: left; padding: 1rem; border: 1px solid var(--color-border); border-radius: 4px; background: white;" onclick="selectPaymentMethod('momo')">
                    <div style="display: flex; align-items: center; gap: 12px; width: 100%;">
                        <i class="ti ti-phone" style="font-size: 24px; color: #A32D2D;"></i>
                        <div style="flex: 1;">
                            <div style="font-weight: 500; font-size: 14px;">Ví MoMo</div>
                            <div style="font-size: 12px; color: var(--color-text-muted);">Thanh toán qua ứng dụng MoMo</div>
                        </div>
                        <i class="ti ti-arrow-right"></i>
                    </div>
                </button>

                <button class="btn" style="text-align: left; padding: 1rem; border: 1px solid var(--color-border); border-radius: 4px; background: white;" onclick="selectPaymentMethod('cash')">
                    <div style="display: flex; align-items: center; gap: 12px; width: 100%;">
                        <i class="ti ti-cash" style="font-size: 24px; color: #3B6D11;"></i>
                        <div style="flex: 1;">
                            <div style="font-weight: 500; font-size: 14px;">Thanh toán tiền mặt</div>
                            <div style="font-size: 12px; color: var(--color-text-muted);">Đến phòng ban quản lý phòng</div>
                        </div>
                        <i class="ti ti-arrow-right"></i>
                    </div>
                </button>
            </div>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 1rem; border-top: 0.5px solid var(--color-border);">
            <button class="btn" onclick="closePaymentModal()">Hủy</button>
        </div>
    </div>
</div>

<script>
    let currentInvoiceId = null;

    function payInvoice(invoiceId) {
        // Fetch invoice details
        fetch(`/api/invoices/${invoiceId}`)
            .then(r => r.json())
            .then(json => {
                if (json.success) {
                    const inv = json.data;
                    currentInvoiceId = invoiceId;
                    document.getElementById('invoicePeriod').textContent = 
                        `Tháng ${inv.month}/${inv.year}`;
                    document.getElementById('invoiceAmount').textContent = 
                        formatVnd(inv.total_amount);
                    document.getElementById('paymentModal').style.display = 'flex';
                }
            });
    }

    function selectPaymentMethod(method) {
        // Redirect to payment gateway or mark as paid
        fetch(`/student/invoices/${currentInvoiceId}/pay`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ method })
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                closePaymentModal();
                if (method === 'bank') {
                    alert('Vui lòng chuyển tiền theo thông tin tài khoản');
                } else if (method === 'momo') {
                    window.location.href = json.redirect; // MoMo payment link
                } else {
                    showToast('success', 'Ghi nhận thanh toán. Vui lòng đến phòng ban quản lý để xác nhận');
                }
                setTimeout(() => location.reload(), 2000);
            }
        });
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
    }

    function filterInvoices() {
        const status = document.getElementById('statusFilter').value;
        const year = document.getElementById('yearFilter').value;
        window.location.search = `?status=${status}&year=${year}`;
    }

    function exportInvoices() {
        window.location.href = '/student/invoices/export?format=excel';
    }
</script>
