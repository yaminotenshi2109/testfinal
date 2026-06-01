<?php
/**
 * app/views/student/violations/index.php
 * Student — Vi phạm của tôi
 */

$activePoints   = (int)($active_points   ?? 0);
$totalPoints    = (int)($total_points    ?? 0);
$threshold      = 10;
$pctBar         = min(100, ($activePoints / $threshold) * 100);
$barColor       = $activePoints >= $threshold ? 'var(--danger)' : ($activePoints >= 7 ? 'var(--warning)' : 'var(--success)');

function violationStatusBadge(string $status): string {
    return match($status) {
        'active'    => '<span class="badge badge-danger">Đang hiệu lực</span>',
        'appealed'  => '<span class="badge badge-warning">⏳ Đang khiếu nại</span>',
        'dismissed' => '<span class="badge badge-neutral">Đã hủy</span>',
        default     => '<span class="badge badge-neutral">' . htmlspecialchars($status) . '</span>',
    };
}
?>

<div class="page-header">
  <div>
    <h1 class="page-title">⚠️ Vi phạm của tôi</h1>
    <p class="page-subtitle">Lịch sử vi phạm và trạng thái điểm</p>
  </div>
</div>

<!-- Points summary card -->
<div class="card mb-24">
  <div class="card-body">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
      <div>
        <div style="font-size:13px;color:var(--txt-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">
          Tổng điểm vi phạm đang hiệu lực
        </div>
        <div style="display:flex;align-items:baseline;gap:6px">
          <span style="font-size:40px;font-weight:800;color:<?= $barColor ?>;line-height:1"><?= $activePoints ?></span>
          <span style="font-size:18px;color:var(--txt-muted)">/ <?= $threshold ?> điểm</span>
        </div>
        <div style="font-size:12px;color:var(--txt-muted);margin-top:4px">
          Tổng lịch sử: <?= $totalPoints ?> điểm (bao gồm đã hủy)
        </div>
      </div>

      <!-- Donut -->
      <div style="text-align:center">
        <div class="donut-ring" style="--pct:<?= round($pctBar * 3.6) ?>deg;background:conic-gradient(<?= $barColor ?> 0deg <?= round($pctBar * 3.6) ?>deg, var(--page-bg) <?= round($pctBar * 3.6) ?>deg)">
          <span class="donut-value" style="color:<?= $barColor ?>"><?= $activePoints ?></span>
        </div>
        <div style="font-size:11px;color:var(--txt-muted);margin-top:6px">Ngưỡng: <?= $threshold ?></div>
      </div>
    </div>

    <div style="margin-top:16px">
      <div class="progress" style="height:10px">
        <div class="progress-bar" style="width:<?= $pctBar ?>%;background:<?= $barColor ?>"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--txt-muted);margin-top:4px">
        <span>0</span>
        <span style="color:var(--warning)">⚠️ Cảnh báo (7)</span>
        <span style="color:var(--danger)">🔴 Xem xét (10)</span>
      </div>
    </div>
  </div>
</div>

<!-- Warning banner if exceeded threshold -->
<?php if ($activePoints >= $threshold): ?>
  <div class="alert alert-error mb-24" style="border-radius:var(--radius)">
    <span class="alert-icon">🚨</span>
    <div class="alert-content">
      <p class="alert-title">Hợp đồng đang bị xem xét!</p>
      <p class="alert-msg">Điểm vi phạm của bạn đã đạt <strong><?= $activePoints ?> điểm</strong>. Hợp đồng thuê phòng của bạn đang ở trạng thái <strong>"Đang xem xét"</strong>. Vui lòng liên hệ ban quản lý để giải quyết.</p>
    </div>
  </div>
<?php elseif ($activePoints >= 7): ?>
  <div class="alert alert-warning mb-24" style="border-radius:var(--radius)">
    <span class="alert-icon">⚠️</span>
    <div class="alert-content">
      <p class="alert-title">Cảnh báo điểm vi phạm</p>
      <p class="alert-msg">Bạn đã có <strong><?= $activePoints ?>/10 điểm</strong> vi phạm. Hãy tuân thủ nội quy ký túc xá để tránh bị xem xét hợp đồng.</p>
    </div>
  </div>
<?php endif; ?>

<!-- Violations list -->
<?php if (empty($violations)): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-icon">✅</div>
      <h3 class="empty-title">Không có vi phạm nào</h3>
      <p class="empty-msg">Bạn chưa có vi phạm nội quy nào. Hãy tiếp tục tuân thủ quy định ký túc xá!</p>
    </div>
  </div>

<?php else: ?>
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Lịch sử vi phạm</div>
        <div class="card-subtitle"><?= count($violations) ?> lần ghi nhận</div>
      </div>
    </div>

    <div class="table-wrapper" style="border-radius:0;border:none;box-shadow:none">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Loại vi phạm</th>
            <th>Mô tả</th>
            <th>Điểm trừ</th>
            <th>Trạng thái</th>
            <th>Ngày ghi</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($violations as $i => $v): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td>
                <span style="font-weight:600"><?= htmlspecialchars($v['violation_type'] ?? '') ?></span>
              </td>
              <td style="max-width:200px">
                <div class="truncate" style="max-width:200px" title="<?= htmlspecialchars($v['description'] ?? '') ?>">
                  <?= htmlspecialchars($v['description'] ?? '') ?>
                </div>
                <?php if (!empty($v['appeal_note'])): ?>
                  <div class="sub" style="color:var(--info)">💬 Khiếu nại: <?= htmlspecialchars($v['appeal_note']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span style="font-weight:800;color:var(--danger);font-size:16px">-<?= htmlspecialchars((string)($v['penalty_points'] ?? 1)) ?></span>
              </td>
              <td><?= violationStatusBadge($v['status'] ?? '') ?></td>
              <td>
                <div><?= !empty($v['created_at']) ? date('d/m/Y', strtotime($v['created_at'])) : '—' ?></div>
                <div class="sub"><?= !empty($v['created_at']) ? date('H:i', strtotime($v['created_at'])) : '' ?></div>
              </td>
              <td>
                <?php if (($v['status'] ?? '') === 'active'): ?>
                  <button class="btn btn-outline btn-sm" onclick="openAppealModal(<?= (int)($v['id'] ?? 0) ?>)">
                    📝 Khiếu nại
                  </button>
                <?php else: ?>
                  <span style="font-size:12px;color:var(--txt-muted)">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- Appeal Modal -->
<div class="modal-overlay" id="modalAppeal">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <h3 class="modal-title">📝 Gửi khiếu nại vi phạm</h3>
      <button class="modal-close" data-modal-close="modalAppeal">×</button>
    </div>
    <div class="modal-body">
      <div class="alert alert-info mb-16">
        <span class="alert-icon">ℹ️</span>
        <div class="alert-content"><p class="alert-msg">Hãy giải trình rõ ràng lý do khiếu nại. Ban quản lý sẽ xem xét trong vòng 5–7 ngày làm việc.</p></div>
      </div>
      <div class="form-group">
        <label class="form-label">Lý do khiếu nại <span class="req">*</span></label>
        <textarea id="appealNote" class="form-control" rows="4" placeholder="Trình bày lý do khiếu nại của bạn..."></textarea>
        <p class="form-error" id="appealError" style="display:none">Vui lòng nhập lý do khiếu nại.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-modal-close="modalAppeal">Hủy</button>
      <button class="btn btn-primary" id="btnSubmitAppeal">
        📤 Gửi khiếu nại
      </button>
    </div>
  </div>
</div>

<script>
let currentViolationId = null;

function openAppealModal(violationId) {
  currentViolationId = violationId;
  document.getElementById('appealNote').value = '';
  document.getElementById('appealError').style.display = 'none';
  window.ktx.openModal('modalAppeal');
}

document.getElementById('btnSubmitAppeal')?.addEventListener('click', async () => {
  const note = document.getElementById('appealNote').value.trim();
  if (!note) {
    document.getElementById('appealError').style.display = 'block';
    return;
  }
  document.getElementById('appealError').style.display = 'none';

  const btn = document.getElementById('btnSubmitAppeal');
  btn.disabled = true;
  btn.innerHTML = '<span class="loading"></span> Đang gửi...';

    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const targetUrl = '<?= getDynamicUrl('/api/violations/') ?>' + currentViolationId + '/appeal';
      await window.ktx.ktxFetch(targetUrl, {
        method: 'POST',
        body: JSON.stringify({ appeal_note: note, _csrf_token: csrf }),
      });
    window.ktx.closeModal('modalAppeal');
    window.ktx.toast('Khiếu nại đã được gửi thành công!', 'success');
    setTimeout(() => location.reload(), 1500);
  } catch (err) {
    window.ktx.toast(err.message || 'Lỗi khi gửi khiếu nại', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '📤 Gửi khiếu nại';
  }
});
</script>
