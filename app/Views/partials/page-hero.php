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
<section class="page-hero on-dark">
  <div class="page-hero-media" style="background-image:url('<?= e(img_url($image)) ?>')" aria-hidden="true"></div>
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
