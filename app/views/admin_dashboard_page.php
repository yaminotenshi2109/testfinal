<?php
/**
 * app/views/admin/dashboard.php
 * ─────────────────────────────────────────────────────────────
 *  Admin dashboard — overview metrics + charts
 *  Rendered within layouts/main.php
 * ─────────────────────────────────────────────────────────────
 */
?>

<!-- Metrics row -->
<div class="metrics-grid">
    <div class="metric-card">
        <span class="metric-label"><i class="ti ti-door" style="margin-right: 4px;"></i>Phòng khả dụng</span>
        <div class="metric-value"><?= $availableRooms ?? 0 ?></div>
        <div class="metric-change positive">
            <i class="ti ti-arrow-up"></i> 12% so với tháng trước
        </div>
    </div>

    <div class="metric-card">
        <span class="metric-label"><i class="ti ti-file-contract" style="margin-right: 4px;"></i>Hợp đồng active</span>
        <div class="metric-value"><?= $activeContracts ?? 0 ?></div>
        <div class="metric-change positive">
            <i class="ti ti-arrow-up"></i> 8% lấp đầy
        </div>
    </div>

    <div class="metric-card">
        <span class="metric-label"><i class="ti ti-receipt" style="margin-right: 4px;"></i>Hóa đơn chưa thanh toán</span>
        <div class="metric-value"><?= $unpaidInvoices ?? 0 ?></div>
        <div class="metric-change negative">
            <i class="ti ti-arrow-down"></i> 5% nợ tăng
        </div>
    </div>

    <div class="metric-card">
        <span class="metric-label"><i class="ti ti-alert-triangle" style="margin-right: 4px;"></i>Vi phạm mới</span>
        <div class="metric-value"><?= $recentViolations ?? 0 ?></div>
        <div class="metric-change negative">
            <i class="ti ti-arrow-up"></i> 3 trường hợp cần xem xét
        </div>
    </div>
</div>

<!-- Charts section -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Occupancy Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tình trạng lấp đầy phòng</h3>
        </div>
        <div style="position: relative; height: 300px;">
            <canvas id="occupancyChart"></canvas>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Doanh thu theo tháng</h3>
        </div>
        <div style="position: relative; height: 300px;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent activity -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Hoạt động gần đây</h3>
        <a href="/admin/registrations" class="btn btn-sm">
            <i class="ti ti-arrow-right"></i>
            <span>Xem tất cả</span>
        </a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Hoạt động</th>
                <th>Sinh viên</th>
                <th>Phòng</th>
                <th>Thời gian</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><i class="ti ti-checklist" style="margin-right: 6px;"></i>Đăng ký phòng</td>
                <td>Nguyễn Văn A</td>
                <td>A1-101</td>
                <td>2 giờ trước</td>
                <td><span class="table-status status-pending">Chờ duyệt</span></td>
            </tr>
            <tr>
                <td><i class="ti ti-file-contract" style="margin-right: 6px;"></i>Ký hợp đồng</td>
                <td>Trần Thị B</td>
                <td>B2-202</td>
                <td>5 giờ trước</td>
                <td><span class="table-status status-active">Hoạt động</span></td>
            </tr>
            <tr>
                <td><i class="ti ti-alert-triangle" style="margin-right: 6px;"></i>Vi phạm ghi nhận</td>
                <td>Lê Văn C</td>
                <td>C3-105</td>
                <td>1 ngày trước</td>
                <td><span class="table-status status-error">Cần xem xét</span></td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    // Chart colors
    const chartColors = {
        primary: '#185FA5',
        success: '#3B6D11',
        warning: '#BA7517',
        danger: '#A32D2D'
    };

    // Occupancy Chart (doughnut)
    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    new Chart(occupancyCtx, {
        type: 'doughnut',
        data: {
            labels: ['Đã lấp đầy', 'Còn trống', 'Bảo trì'],
            datasets: [{
                data: [65, 25, 10],
                backgroundColor: [chartColors.success, chartColors.warning, '#D3D1C7']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Revenue Chart (line)
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6'],
            datasets: [{
                label: 'Doanh thu (triệu VND)',
                data: [45, 52, 48, 61, 55, 67],
                borderColor: chartColors.primary,
                backgroundColor: 'rgba(24, 95, 165, 0.05)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: chartColors.primary,
                pointBorderColor: 'white',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
