<?php
/**
 * app/views/components/breadcrumb.php
 * Usage: include with $breadcrumbs = [['label'=>'Home','url'=>'/'], ['label'=>'Rooms']]
 */

if (empty($breadcrumbs)) return;
?>
<nav aria-label="breadcrumb" style="margin-bottom:16px">
  <ol style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;list-style:none;font-size:13px;color:var(--txt-muted)">
    <?php foreach ($breadcrumbs as $i => $crumb): ?>
      <?php $isLast = ($i === count($breadcrumbs) - 1); ?>
      <li style="display:flex;align-items:center;gap:6px">
        <?php if (!$isLast && isset($crumb['url'])): ?>
          <a href="<?= htmlspecialchars($crumb['url']) ?>" style="color:var(--brand);font-weight:500">
            <?= htmlspecialchars($crumb['label']) ?>
          </a>
          <span style="color:var(--border)">/</span>
        <?php else: ?>
          <span style="color:var(--txt-primary);font-weight:600"><?= htmlspecialchars($crumb['label']) ?></span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</nav>
