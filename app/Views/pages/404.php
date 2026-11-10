<?php /** @var array $c */ $ui = $c['ui']; ?>
<?= partial('page-hero', [
    'image'   => 'img/bandeau-chantiers.jpg',
    'crumbs'  => [$ui['nav_home'] => url(), $ui['notfound_crumb'] => null],
    'eyebrow' => $ui['notfound_eyebrow'],
    'title'   => $ui['notfound_title'],
]) ?>

<section class="section">
  <div class="wrap">
    <p><?= e($ui['notfound_text']) ?></p>
    <p style="margin-top:24px"><a class="btn btn-primary" href="<?= e(url()) ?>"><?= e($ui['notfound_button']) ?> <?= icon('arrow') ?></a></p>
  </div>
</section>
