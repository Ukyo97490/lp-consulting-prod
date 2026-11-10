<?php
/** @var array $c  @var string $slug  @var array $mission  @var string[] $others */
$m = $mission;
$ui = $c['ui'];
?>
<?= partial('page-hero', [
    'image'   => $m['hero_image'],
    'crumbs'  => [$ui['nav_home'] => url(), $ui['mission_crumb'] => url() . '#missions', $m['short'] => null],
    'eyebrow' => $m['eyebrow'],
    'title'   => $m['h1'],
]) ?>

<section class="section">
  <div class="wrap split">
    <div class="reveal">
      <span class="eyebrow"><?= e($m['short']) ?></span>
      <h2><?= e($m['intro_title']) ?></h2>
      <?= paras($m['intro'], 'intro-text') ?>

    </div>
    <div class="split-media reveal reveal-delay-2">
      <?= picture($m['split_image'], image_alt($m['split_image'], $m['short'] . ', ' . $c['site']['name']), ['sizes' => '(max-width: 960px) 100vw, 560px']) ?>
<?php if ($m['split_label'] !== ''): ?>
      <span class="badge badge-visual"><?= e($m['split_label']) ?></span>
<?php endif ?>
    </div>
  </div>
</section>

<section class="section section-mist">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><?= e($ui['mission_services_eyebrow']) ?></span>
      <h2><?= e($ui['mission_services_title']) ?></h2>
    </div>
    <div class="services">
<?php foreach ($m['services'] as $k => $service): ?>
      <div class="service reveal reveal-delay-<?= $k % 2 ?>">
        <span class="service-num"><?= sprintf('%02d', $k + 1) ?></span>
        <h3><?= e($service['title']) ?></h3>
        <p><?= e($service['text']) ?></p>
      </div>
<?php endforeach ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="objective reveal">
      <span class="objective-icon"><?= icon('target') ?></span>
      <div>
        <h2><?= e($ui['mission_objective_title']) ?></h2>
        <p><?= e($m['objective']) ?></p>
      </div>
    </div>
  </div>
</section>

<?= partial('cta', ['title' => $m['cta_title'], 'text' => $m['cta_text'], 'button' => $ui['cta_button']]) ?>

<section class="section">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><?= e($ui['mission_others_eyebrow']) ?></span>
      <h2><?= e($ui['mission_others_title']) ?></h2>
    </div>
    <div class="other-missions reveal">
<?php foreach ($others as $o): $om = $c['missions'][$o]; ?>
      <a href="<?= e(url($o)) ?>"><span><small><?= e($ui['mission_others_label']) ?> <?= e($om['num']) ?></small><?= e($om['short']) ?></span><?= icon('arrow') ?></a>
<?php endforeach ?>
    </div>
  </div>
</section>
