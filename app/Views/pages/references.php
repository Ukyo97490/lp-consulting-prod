<?php
/** @var array $c  @var array $groups [['title' => …, 'projects' => […]], …] */
$r = $c['references'];
?>
<?= partial('page-hero', [
    'image'   => $r['hero_image'],
    'crumbs'  => ['Accueil' => url(), 'Références' => null],
    'eyebrow' => 'Références',
    'title'   => $r['title'],
    'text'    => $r['text'],
]) ?>

<section class="section">
  <div class="wrap">
<?php foreach ($groups as $group): ?>
    <div class="ref-group">
      <h2 class="ref-group-title"><?= e($group['title']) ?></h2>
      <div class="ref-grid">
<?php foreach ($group['projects'] as $i => $p): ?>
        <?= partial('project-card', ['p' => $p, 'i' => $i]) ?>
<?php endforeach ?>
      </div>
    </div>
<?php endforeach ?>
    <p class="missions-note"><?= e($r['note']) ?></p>
  </div>
</section>

<?= partial('cta', ['title' => $r['cta_title'], 'text' => $r['cta_text']]) ?>
