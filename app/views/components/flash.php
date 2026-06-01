<?php
/**
 * app/views/components/flash.php
 * Standalone flash message renderer.
 * Usage: include with $_flash in scope OR pass $messages = [...]
 */

$messages = $messages ?? $_flash ?? [];
if (empty($messages)) return;

$icons = [
    'success' => ['alert-success', '✅'],
    'error'   => ['alert-error',   '❌'],
    'danger'  => ['alert-error',   '❌'],
    'warning' => ['alert-warning', '⚠️'],
    'info'    => ['alert-info',    'ℹ️'],
];
?>

<?php foreach ($messages as $msg): ?>
  <?php
    $type = $msg['type'] ?? 'info';
    [$cls, $icon] = $icons[$type] ?? ['alert-info', 'ℹ️'];
  ?>
  <div class="alert <?= $cls ?>">
    <span class="alert-icon"><?= $icon ?></span>
    <div class="alert-content">
      <p class="alert-msg"><?= htmlspecialchars($msg['message'] ?? '') ?></p>
    </div>
    <button class="alert-close" title="Đóng">×</button>
  </div>
<?php endforeach; ?>
