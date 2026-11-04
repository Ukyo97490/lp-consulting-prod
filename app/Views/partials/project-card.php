<?php
/** @var array $p projet  @var int $i position (décalage d'animation) */
use App\Services\ContentSchema;

$badge = ContentSchema::IMAGE_TYPES[$p['image_type']] ?? '';
?>
<article class="ref-card reveal reveal-delay-<?= $i % 3 ?>">
  <div class="ref-media">
    <img src="<?= e(img_url($p['image'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
<?php if ($badge !== ''): ?>
    <span class="badge badge-visual"><?= e($badge) ?></span>
<?php endif ?>
  </div>
  <div class="ref-body">
    <span class="status status-<?= e($p['status']) ?>"><?= e(ContentSchema::STATUS[$p['status']] ?? '') ?></span>
    <div class="ref-client"><?= e($p['client']) ?></div>
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
