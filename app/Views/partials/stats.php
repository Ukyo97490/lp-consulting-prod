<?php
/** @var array $items [['prefix' => …, 'value' => …, 'label' => …], …] */
if (!$items) return;
?>
<section class="stats" aria-label="Chiffres clés">
  <div class="wrap">
    <div class="stats-grid<?= count($items) === 2 ? ' stats-grid--duo' : '' ?>">
<?php foreach ($items as $st):
    $prefix = trim((string) $st['prefix']);
    $prefix = $prefix !== '' ? $prefix . ' ' : '';
    $animated = ctype_digit((string) $st['value']) && (int) $st['value'] < 1900; // les années ne sont pas animées
?>
      <div class="stat"><strong<?php if ($animated): ?> data-count="<?= e($st['value']) ?>" data-prefix="<?= e($prefix) ?>"<?php endif ?>><?= e($prefix . $st['value']) ?></strong><span><?= e($st['label']) ?></span></div>
<?php endforeach ?>
    </div>
  </div>
</section>
