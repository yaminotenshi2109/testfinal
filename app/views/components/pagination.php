<?php
/**
 * app/views/components/pagination.php
 * Usage: include with $pagination = [...] in scope
 *
 * $pagination = [
 *   'data'         => [...],
 *   'total'        => 100,
 *   'per_page'     => 15,
 *   'current_page' => 1,
 *   'last_page'    => 7,
 *   'from'         => 1,
 *   'to'           => 15,
 * ]
 */

if (empty($pagination) || $pagination['last_page'] <= 1) return;

$current  = (int)$pagination['current_page'];
$last     = (int)$pagination['last_page'];
$from     = (int)($pagination['from'] ?? 0);
$to       = (int)($pagination['to']   ?? 0);
$total    = (int)($pagination['total'] ?? 0);

// Build URL with page param
$baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
$query   = $_GET;
unset($query['page']);

function pageUrl(int $page, array $query, string $base): string
{
    $q = array_merge($query, ['page' => $page]);
    return $base . '?' . http_build_query($q);
}

$window = 2; // pages on each side of current
$pages  = [];
for ($p = max(1, $current - $window); $p <= min($last, $current + $window); $p++) {
    $pages[] = $p;
}
?>

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:14px 20px;border-top:1px solid var(--border);">
  <div class="pagination-info">
    Hiển thị <strong><?= $from ?></strong>–<strong><?= $to ?></strong> trong <strong><?= number_format($total) ?></strong> kết quả
  </div>

  <div class="pagination">
    <!-- Previous -->
    <?php if ($current > 1): ?>
      <a href="<?= htmlspecialchars(pageUrl($current - 1, $query, $baseUrl)) ?>" class="page-link" title="Trang trước">‹</a>
    <?php else: ?>
      <span class="page-link disabled">‹</span>
    <?php endif; ?>

    <!-- First page if not in window -->
    <?php if ($pages[0] > 1): ?>
      <a href="<?= htmlspecialchars(pageUrl(1, $query, $baseUrl)) ?>" class="page-link">1</a>
      <?php if ($pages[0] > 2): ?>
        <span class="page-link disabled" style="border:none;padding:0 4px">…</span>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Page window -->
    <?php foreach ($pages as $p): ?>
      <?php if ($p === $current): ?>
        <span class="page-link active"><?= $p ?></span>
      <?php else: ?>
        <a href="<?= htmlspecialchars(pageUrl($p, $query, $baseUrl)) ?>" class="page-link"><?= $p ?></a>
      <?php endif; ?>
    <?php endforeach; ?>

    <!-- Last page if not in window -->
    <?php if (end($pages) < $last): ?>
      <?php if (end($pages) < $last - 1): ?>
        <span class="page-link disabled" style="border:none;padding:0 4px">…</span>
      <?php endif; ?>
      <a href="<?= htmlspecialchars(pageUrl($last, $query, $baseUrl)) ?>" class="page-link"><?= $last ?></a>
    <?php endif; ?>

    <!-- Next -->
    <?php if ($current < $last): ?>
      <a href="<?= htmlspecialchars(pageUrl($current + 1, $query, $baseUrl)) ?>" class="page-link" title="Trang sau">›</a>
    <?php else: ?>
      <span class="page-link disabled">›</span>
    <?php endif; ?>
  </div>
</div>
