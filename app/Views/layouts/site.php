<?php
/** @var array $c contenus du site  @var array $meta métadonnées de la page  @var string $content corps de la page */
$s = $c['site'];
$canonical = abs_url($meta['path']);
$nav = [
    ''                     => 'Accueil',
    'etudes-techniques'    => 'Études techniques',
    'preparation-chantier' => 'Préparation',
    'direction-travaux'    => 'Direction de travaux',
    'references'           => 'Références',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($meta['title']) ?></title>
  <meta name="description" content="<?= e($meta['description']) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($meta['noindex']): ?>
  <meta name="robots" content="noindex, follow">
<?php endif ?>
  <meta name="theme-color" content="#0c3f5e">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="fr_FR">
  <meta property="og:site_name" content="<?= e($s['name']) ?>">
  <meta property="og:title" content="<?= e($meta['title']) ?>">
  <meta property="og:description" content="<?= e($meta['description']) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:image" content="<?= e(abs_url('img/og-image.jpg')) ?>">
  <link rel="icon" type="image/png" href="<?= e(url('img/logo-mark.png')) ?>">
  <link rel="preload" href="<?= e(url('fonts/montserrat-latin-700-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<?php if ($meta['jsonld']): ?>
  <script type="application/ld+json"><?= json_encode([
      '@context' => 'https://schema.org', '@type' => 'ProfessionalService', 'name' => $s['name'], 'slogan' => $s['tagline'],
      'description' => $meta['description'], 'url' => abs_url(), 'logo' => abs_url('img/logo.png'), 'email' => $s['email'],
      'telephone' => tel_href($s['phone']), 'areaServed' => ['@type' => 'Place', 'name' => 'La Réunion'],
      'address' => ['@type' => 'PostalAddress', 'addressRegion' => 'La Réunion', 'addressCountry' => 'FR'],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif ?>
</head>
<body>
  <a class="skip-link" href="#contenu">Aller au contenu</a>

  <header class="site-header">
    <div class="wrap header-inner">
      <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($s['name']) ?>, accueil">
        <img src="<?= e(url('img/logo-header.png')) ?>" alt="<?= e($s['name'] . ' : ' . $s['tagline']) ?>" width="520" height="195">
      </a>
      <nav class="main-nav" id="main-nav" aria-label="Navigation principale">
        <ul>
<?php foreach ($nav as $path => $label): ?>
          <li><a href="<?= e(url($path)) ?>"<?= $meta['nav'] === $path ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
<?php endforeach ?>
          <li><a class="nav-cta" href="<?= e(url()) ?>#contact">Nous contacter</a></li>
          <li><a class="nav-admin" href="<?= e(url('admin')) ?>" rel="nofollow" title="Administration" aria-label="Administration"><?= icon('gear') ?><span>Administration</span></a></li>
        </ul>
      </nav>
      <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Ouvrir le menu"><span></span></button>
    </div>
  </header>

  <main id="contenu"><?= $content ?></main>

  <?= partial('footer', ['c' => $c]) ?>

  <button class="to-top" type="button" aria-label="Revenir en haut de page"><?= icon('up') ?></button>

  <nav class="mobile-bar" aria-label="Contact rapide">
    <a href="tel:<?= e(tel_href($s['phone'])) ?>"><?= icon('phone') ?> Appeler</a>
    <a href="<?= e(url()) ?>#contact"><?= icon('mail') ?> Écrire</a>
  </nav>

  <script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
