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
$link = safe_link($h['expertise_link']);
$figures = $h['expertise_figures'];
?>
<section class="hero on-dark">
  <div class="hero-media" style="background-image:url('<?= e(img_url($h['hero_image'])) ?>')" role="img" aria-label="Photo de chantier"></div>
  <div class="wrap">
    <div class="hero-content">
      <span class="eyebrow"><?= e($h['hero_eyebrow']) ?></span>
      <h1><?= e($h['hero_title']) ?> <em><?= e($h['hero_highlight']) ?></em></h1>
      <p class="hero-lead"><?= e($h['hero_lead']) ?></p>
      <div class="hero-actions">
        <a class="btn btn-sky" href="#missions">Découvrir les missions <?= icon('arrow') ?></a>
        <a class="btn btn-ghost" href="#contact">Contacter LP CONSULTING</a>
      </div>
      <div class="hero-tags"><?php foreach ($h['hero_tags'] as $tag): ?><span><?= e($tag) ?></span><?php endforeach ?></div>
    </div>
  </div>
  <img class="hero-mark" src="<?= e(url('img/logo-mark-blanc.png')) ?>" alt="" aria-hidden="true">
</section>

<section class="stats" aria-label="Chiffres clés">
  <div class="wrap">
    <div class="stats-grid">
<?php foreach ($h['stats'] as $st):
    $prefix = trim((string) $st['prefix']);
    $prefix = $prefix !== '' ? $prefix . ' ' : '';
    $animated = ctype_digit((string) $st['value']) && (int) $st['value'] < 1900; // les années ne sont pas animées
?>
      <div class="stat"><strong<?php if ($animated): ?> data-count="<?= e($st['value']) ?>" data-prefix="<?= e($prefix) ?>"<?php endif ?>><?= e($prefix . $st['value']) ?></strong><span><?= e($st['label']) ?></span></div>
<?php endforeach ?>
    </div>
  </div>
</section>

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
        <img src="<?= e(img_url($h['about_image'])) ?>" alt="Grands chantiers d’infrastructure et de bâtiment" loading="lazy" style="object-position:30% center">
        <figcaption><?= e($h['about_caption']) ?></figcaption>
      </figure>
      <div class="groups">
        <p>Expérience au sein de</p>
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
        <span class="more">En savoir plus <?= icon('arrow') ?></span>
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
      <a href="<?= e(url('references')) ?>"><img src="<?= e(img_url($p['image'])) ?>" alt="" loading="lazy"><span><?= e($p['title']) ?><small><?= e($p['role']) ?> · <?= e($p['budget']) ?></small></span></a>
<?php endforeach ?>
    </div>
    <p style="margin-top:32px"><a class="btn btn-outline" href="<?= e(url('references')) ?>">Voir toutes les références <?= icon('arrow') ?></a></p>
  </div>
</section>

<?= partial('cta', ['title' => $h['cta_title'], 'text' => $h['cta_text']]) ?>

<section class="section section-mist" id="contact">
  <div class="wrap">
    <div class="section-head reveal">
      <span class="eyebrow">Contact</span>
      <h2><?= e($h['contact_title']) ?></h2>
      <p><?= e($h['contact_text']) ?></p>
    </div>
    <div class="contact-grid">
      <div class="contact-info reveal">
        <h3><?= e($s['name']) ?></h3>
        <p>Études techniques, préparation de chantier et direction de travaux pour les entreprises du BTP.</p>
        <ul class="contact-list">
          <li><?= icon('mail') ?><div><small>E-mail</small><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></div></li>
          <li><?= icon('phone') ?><div><small>Téléphone</small><a href="tel:<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a></div></li>
          <li><?= icon('pin') ?><div><small>Zone d’intervention</small><span><?= e($s['zone']) ?></span></div></li>
        </ul>
      </div>
      <?= partial('contact-form', ['status' => $contactStatus, 'token' => $contactToken, 'missions' => $missions]) ?>
    </div>
  </div>
</section>
