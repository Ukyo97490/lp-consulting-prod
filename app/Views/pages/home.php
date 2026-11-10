<?php
/**
 * @var array   $c              contenus du site
 * @var array[] $featured       projets mis en avant (4 maximum)
 * @var ?string $contactStatus  retour du formulaire sans JavaScript
 * @var string  $contactToken
 * @var string[] $missions      choix du formulaire
 */
use App\Services\ContentSchema;

$h = $c['home'];
$s = $c['site'];
$ui = $c['ui'];
$link = safe_link($h['expertise_link']);
$figures = $h['expertise_figures'];
?>
<?php preload_image($h['hero_image']) ?>
<section class="hero on-dark">
  <div class="hero-media"><?= picture($h['hero_image'], image_alt($h['hero_image'], 'Photo de chantier'), ['fetchpriority' => 'high', 'sizes' => '100vw']) ?></div>
  <div class="wrap">
    <div class="hero-content">
      <span class="eyebrow"><?= e($h['hero_eyebrow']) ?></span>
      <h1><?= e($h['hero_title']) ?> <em><?= e($h['hero_highlight']) ?></em></h1>
      <p class="hero-lead"><?= e($h['hero_lead']) ?></p>
      <div class="hero-actions">
        <a class="btn btn-sky" href="#missions"><?= e($h['hero_btn_missions']) ?> <?= icon('arrow') ?></a>
        <a class="btn btn-ghost" href="#contact"><?= e($h['hero_btn_contact']) ?></a>
      </div>
      <div class="hero-tags"><?php foreach ($h['hero_tags'] as $tag): ?><span><?= e($tag) ?></span><?php endforeach ?></div>
    </div>
  </div>
</section>

<?= partial('stats', ['items' => $h['stats']]) ?>

<section class="section" id="presentation">
  <div class="wrap about-grid">
    <div class="about-text reveal">
      <span class="eyebrow"><?= e($h['about_eyebrow']) ?></span>
      <h2><?= e($h['about_title']) ?></h2>
      <p class="lead"><?= e($h['about_lead']) ?></p>
      <?= paras($h['about_text']) ?>

      <ol class="timeline">
<?php foreach ($h['timeline'] as $step): ?>
        <li><strong><?= e($step['title']) ?></strong><span><?= e($step['text']) ?></span></li>
<?php endforeach ?>
      </ol>
    </div>
    <aside class="about-aside reveal reveal-delay-2">
      <figure class="about-figure">
        <?= picture($h['about_image'], image_alt($h['about_image'], 'Grands chantiers d’infrastructure et de bâtiment'), ['style' => 'object-position:30% center', 'sizes' => '(max-width: 960px) min(520px, 100vw), 420px']) ?>
        <figcaption><?= e($h['about_caption']) ?></figcaption>
      </figure>
      <div class="groups">
        <p><?= e($h['groups_title']) ?></p>
        <ul><?php foreach ($h['groups'] as $g): ?><li><?= e($g) ?></li><?php endforeach ?></ul>
      </div>
    </aside>
  </div>
</section>

<section class="section section-mist" id="missions">
  <div class="wrap">
    <div class="section-head center reveal">
      <span class="eyebrow"><?= e($h['missions_eyebrow']) ?></span>
      <h2><?= e($h['missions_title']) ?></h2>
      <p><?= e($h['missions_text']) ?></p>
    </div>
    <div class="missions-grid">
<?php foreach (ContentSchema::MISSIONS as $k => $slug): $m = $c['missions'][$slug]; ?>
      <a class="mission-card reveal reveal-delay-<?= $k ?>" href="<?= e(url($slug)) ?>">
        <span class="mission-num" aria-hidden="true"><?= e($m['num']) ?></span>
        <span class="mission-icon"><?= icon($m['icon']) ?></span>
        <h3><?= e($m['short']) ?></h3>
        <p><?= e($m['card_text']) ?></p>
        <ul><?php foreach ($m['card_points'] as $pt): ?><li><?= e($pt) ?></li><?php endforeach ?></ul>
        <span class="more"><?= e($ui['mission_more']) ?> <?= icon('arrow') ?></span>
      </a>
<?php endforeach ?>
    </div>
  </div>
</section>

<section class="section highlight on-dark">
  <div class="wrap highlight-grid">
    <div class="reveal">
      <span class="eyebrow"><?= e($h['expertise_eyebrow']) ?></span>
      <h2><?= e($h['expertise_title']) ?></h2>
      <?= paras($h['expertise_text']) ?>

<?php if ($link !== ''): ?>
      <a class="btn btn-ghost" href="<?= e($link) ?>" target="_blank" rel="noopener"><?= e($h['expertise_link_label']) ?> <?= icon('download') ?></a>
<?php endif ?>
    </div>
    <div class="highlight-figures reveal reveal-delay-2">
<?php foreach ($figures as $k => $f): $wide = $k === count($figures) - 1 && count($figures) % 2; ?>
      <div<?= $wide ? ' class="wide"' : '' ?>><strong><?= e($f['value']) ?></strong><span><?= e($f['label']) ?></span></div>
<?php endforeach ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><?= e($h['refs_eyebrow']) ?></span>
      <h2><?= e($h['refs_title']) ?></h2>
      <p><?= e($h['refs_text']) ?></p>
    </div>
    <div class="ref-strip reveal">
<?php foreach ($featured as $p): ?>
      <a href="<?= e(url('references')) ?>"><?= picture($p['image'], project_alt($p), ['sizes' => '(max-width: 960px) 50vw, 300px']) ?><span><?= e($p['title']) ?><small><?= e($p['role']) ?> · <?= e($p['budget']) ?></small></span></a>
<?php endforeach ?>
    </div>
    <p style="margin-top:32px"><a class="btn btn-outline" href="<?= e(url('references')) ?>"><?= e($h['refs_button']) ?> <?= icon('arrow') ?></a></p>
  </div>
</section>

<?= partial('cta', ['title' => $h['cta_title'], 'text' => $h['cta_text'], 'button' => $ui['cta_button']]) ?>

<section class="section section-mist" id="contact">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow"><?= e($h['contact_eyebrow']) ?></span>
      <h2><?= e($h['contact_title']) ?></h2>
      <p><?= e($h['contact_text']) ?></p>
    </div>
    <div class="contact-grid">
      <div class="contact-info reveal">
        <h3><?= e($s['name']) ?></h3>
        <p><?= e($h['contact_intro']) ?></p>
        <ul class="contact-list">
          <li><?= icon('mail') ?><div><small><?= e($ui['form_email']) ?></small><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></div></li>
          <li><?= icon('phone') ?><div><small><?= e($ui['form_phone']) ?></small><a href="tel:<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a></div></li>
          <li><?= icon('pin') ?><div><small><?= e($ui['contact_zone']) ?></small><span><?= e($s['zone']) ?></span></div></li>
        </ul>
      </div>
      <?= partial('contact-form', ['status' => $contactStatus, 'token' => $contactToken, 'missions' => $missions, 'ui' => $ui]) ?>
    </div>
  </div>
</section>
