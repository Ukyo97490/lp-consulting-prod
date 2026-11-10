<?php
/** @var array $p projet  @var int $i position (décalage d'animation)  @var array $r textes de la page Références (libellés) */
$badge = $r['badge_' . $p['image_type']] ?? '';
?>
<article class="ref-card reveal reveal-delay-<?= $i % 3 ?>">
  <div class="ref-media">
    <?= picture($p['image'], project_alt($p), ['sizes' => '(max-width: 680px) 100vw, (max-width: 960px) 50vw, 400px']) ?>
<?php if ($badge !== ''): ?>
    <span class="badge badge-visual"><?= e($badge) ?></span>
<?php endif ?>
  </div>
  <div class="ref-body">
    <span class="status status-<?= e($p['status']) ?>"><?= e($r['status_' . $p['status']] ?? '') ?></span>
<?php if ($p['client'] !== ''): ?>
    <div class="ref-client"><?= e($p['client']) ?></div>
<?php endif ?>
    <h3><?= e($p['title']) ?></h3>
    <div class="ref-meta">
      <span><?= e($p['role']) ?></span>
<?php if ($p['duration'] !== ''): ?>
      <span><?= e($p['duration']) ?></span>
<?php endif ?>
    </div>
    <p><?= e($p['description']) ?></p>
    <div class="ref-budget"><?= e($p['budget']) ?><?php if ($p['budget_note'] !== ''): ?><small><?= e($p['budget_note']) ?></small><?php endif ?></div>
  </div>
</article>
