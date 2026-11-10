<?php
/**
 * @var string $image
 * @var array  $crumbs  [libellé => chemin|null] (le dernier élément n'a pas de lien)
 * @var string $eyebrow
 * @var string $title
 * @var string $text
 */
$eyebrow ??= '';
$text ??= '';
$links = [];
foreach ($crumbs as $label => $href) $links[] = $href === null ? e($label) : '<a href="' . e($href) . '">' . e($label) . '</a>';
?>
<?php preload_image($image) ?>
<section class="page-hero on-dark">
  <div class="page-hero-media" aria-hidden="true"><?= picture($image, image_alt($image, 'Grands chantiers d’infrastructure et de bâtiment'), ['fetchpriority' => 'high', 'sizes' => '100vw']) ?></div>
  <div class="wrap">
    <nav class="breadcrumb" aria-label="Fil d’Ariane"><?= implode(' / ', $links) ?></nav>
<?php if ($eyebrow !== ''): ?>
    <span class="eyebrow"><?= e($eyebrow) ?></span>
<?php endif ?>
    <h1><?= e($title) ?></h1>
<?php if ($text !== ''): ?>
    <p><?= e($text) ?></p>
<?php endif ?>
  </div>
</section>
