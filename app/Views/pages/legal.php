<?php
/** @var array $c */
$l = $c['legal'];
$s = $c['site'];
// Les valeurs encore à compléter, entre crochets, sont surlignées.
$v = fn(string $value) => preg_match('/\[.*\]/', $value) ? '<span class="placeholder">' . e($value) . '</span>' : e($value);
$mail = '<a href="mailto:' . e($s['email']) . '">' . e($s['email']) . '</a>';
?>
<?= partial('page-hero', [
    'image'  => 'img/bandeau-chantiers.jpg',
    'crumbs' => ['Accueil' => url(), 'Mentions légales' => null],
    'title'  => 'Mentions légales',
]) ?>

<section class="section">
  <div class="wrap legal">
    <h2>Éditeur du site</h2>
    <p>
      <?= e($l['company']) ?>, <?= $v($l['legal_form']) ?><br>
      Siège : <?= $v($l['address']) ?><br>
      SIRET : <?= $v($l['siret']) ?><br>
      E-mail : <?= $mail ?> · Téléphone : <?= e($s['phone']) ?><br>
      Directeur de la publication : <?= $v($l['director']) ?>
    </p>

    <h2>Conception et réalisation</h2>
    <p><?= e($l['designer']) ?></p>

    <h2>Hébergement</h2>
    <p><?= nl2br($v($l['host']), false) ?></p>

    <h2>Propriété intellectuelle</h2>
    <p>L’ensemble des contenus de ce site (textes, logo, visuels) est la propriété de <?= e($l['company']) ?> ou de leurs auteurs respectifs. Les visuels de projets sont présentés à titre d’illustration des opérations auxquelles <?= e($l['company']) ?> ou son fondateur ont contribué. Toute reproduction sans autorisation est interdite.</p>

    <h2>Données personnelles</h2>
    <p>Les informations transmises via le formulaire de contact (nom, entreprise, e-mail, téléphone, message) sont enregistrées par <?= e($l['company']) ?> dans le seul but de répondre à votre demande. Elles ne sont ni cédées ni revendues et sont conservées au maximum trois ans après le dernier échange. Conformément au RGPD, vous disposez d’un droit d’accès, de rectification et de suppression de vos données en écrivant à <?= $mail ?>. Vous pouvez également introduire une réclamation auprès de la CNIL.</p>

    <h2>Cookies</h2>
    <p>Ce site n’utilise aucun cookie sur les pages publiques, ni outil de mesure d’audience. Les polices de caractères sont hébergées sur le site : aucune donnée n’est transmise à un service tiers.</p>
  </div>
</section>
