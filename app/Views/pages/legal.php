<?php
/** @var array $c */
$l = $c['legal'];
$s = $c['site'];
// Les valeurs encore à compléter, entre crochets, sont surlignées.
$v = fn(string $value) => preg_match('/\[.*\]/', $value) ? '<span class="placeholder">' . e($value) . '</span>' : e($value);
$mail = '<a href="mailto:' . e($s['email']) . '">' . e($s['email']) . '</a>';
// Textes libres : {societe} et {email} sont remplacés par la raison sociale et l'adresse e-mail du site.
$t = fn(string $text) => strtr(nl2br(e($text), false), ['{societe}' => e($l['company']), '{email}' => $mail]);
?>
<?= partial('page-hero', [
    'image'  => 'img/bandeau-chantiers.jpg',
    'crumbs' => [$c['ui']['nav_home'] => url(), $l['title'] => null],
    'title'  => $l['title'],
]) ?>

<section class="section">
  <div class="wrap legal">
    <h2><?= e($l['editor_title']) ?></h2>
    <p>
      <?= e($l['company']) ?>, <?= $v($l['legal_form']) ?><br>
      <?= e($l['label_address']) ?> : <?= $v($l['address']) ?><br>
      <?= e($l['label_siret']) ?> : <?= $v($l['siret']) ?><br>
      <?= e($l['label_email']) ?> : <?= $mail ?> · <?= e($l['label_phone']) ?> : <?= e($s['phone']) ?><br>
      <?= e($l['label_director']) ?> : <?= $v($l['director']) ?>
    </p>

    <h2><?= e($l['designer_title']) ?></h2>
    <p><?= e($l['designer']) ?></p>

    <h2><?= e($l['host_title']) ?></h2>
    <p><?= nl2br($v($l['host']), false) ?></p>

    <h2><?= e($l['ip_title']) ?></h2>
    <p><?= $t($l['ip_text']) ?></p>

    <h2><?= e($l['privacy_title']) ?></h2>
    <p><?= $t($l['privacy_text']) ?></p>

    <h2><?= e($l['cookies_title']) ?></h2>
    <p><?= $t($l['cookies_text']) ?></p>
  </div>
</section>
