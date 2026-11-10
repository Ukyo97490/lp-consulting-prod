<?php
/** @var array $c contenus du site  @var array $meta métadonnées de la page  @var string $content corps de la page */
$s = $c['site'];
$ui = $c['ui'];
$canonical = abs_url($meta['path']);
$nav = ['' => $ui['nav_home']];
foreach (\App\Services\ContentSchema::MISSIONS as $slug) $nav[$slug] = $ui['nav_' . $slug];
$nav['references'] = $ui['nav_references'];
// Adresse du siège (Coordonnées et mentions) découpée pour les données structurées : « rue, code postal ville, région ».
$addr = array_map('trim', explode(',', (string) $c['legal']['address']));
$postal = [];
foreach ($addr as $i => $part) {
    if (preg_match('/^(\d{5})\s+(.+)$/', $part, $postal)) { $postal[] = $i; break; }
    $postal = [];
}
$address = ['@type' => 'PostalAddress', 'streetAddress' => $postal ? implode(', ', array_slice($addr, 0, $postal[3])) : implode(', ', $addr), 'addressCountry' => 'FR'];
if ($postal) {
    $address += ['postalCode' => $postal[1], 'addressLocality' => $postal[2]];
    if (isset($addr[$postal[3] + 1])) $address['addressRegion'] = $addr[$postal[3] + 1];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <script>document.documentElement.classList.add('js')</script>
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
  <link rel="icon" type="image/png" sizes="48x48" href="<?= e(url(is_file(PUBLIC_PATH . '/img/logo-favicon.png') ? 'img/logo-favicon.png' : 'img/logo-mark.png')) ?>">
<?php if ($lcp = preload_image()): ?>
  <link rel="preload" as="image" href="<?= e($lcp['href']) ?>"<?php if ($lcp['srcset'] !== ''): ?> imagesrcset="<?= e($lcp['srcset']) ?>" imagesizes="<?= e($lcp['sizes']) ?>"<?php endif ?> fetchpriority="high">
<?php endif ?>
  <link rel="preload" href="<?= e(url('fonts/montserrat-latin-700-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="<?= e(url('fonts/source-sans-3-latin-400-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<?php if (($critical = critical_css($meta['nav'] === '' ? 'accueil' : 'pages')) !== null): ?>
  <style><?= $critical ?></style>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>" media="print" id="css-full">
  <script>(function(l){function a(){l.media='all'}if(l.sheet)a();else l.addEventListener('load',a)})(document.getElementById('css-full'))</script>
  <noscript><link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>"></noscript>
<?php else: ?>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<?php endif ?>
<?php if ($meta['jsonld']): ?>
  <script type="application/ld+json"><?= json_encode([
      '@context' => 'https://schema.org', '@type' => 'ProfessionalService', 'name' => $s['name'], 'slogan' => $s['tagline'],
      'description' => $meta['description'], 'url' => abs_url(), 'logo' => abs_url('img/logo.png'), 'image' => abs_url('img/og-image.jpg'),
      'email' => $s['email'], 'telephone' => tel_href($s['phone']),
      'areaServed' => [['@type' => 'Place', 'name' => 'La Réunion'], ['@type' => 'Place', 'name' => 'Mayotte']],
      'address' => $address,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif ?>
</head>
<body>
  <a class="skip-link" href="#contenu">Aller au contenu</a>

  <header class="site-header">
    <div class="wrap header-inner">
      <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($s['name']) ?>, accueil">
        <?= picture('img/logo-header.png', $s['name'] . ' : ' . $s['tagline'], ['loading' => 'eager', 'sizes' => '160px']) ?>
      </a>
      <nav class="main-nav" id="main-nav" aria-label="Navigation principale">
        <ul>
<?php foreach ($nav as $path => $label): ?>
          <li><a href="<?= e(url($path)) ?>"<?= $meta['nav'] === $path ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
<?php endforeach ?>
          <li><a class="nav-cta" href="<?= e(url()) ?>#contact"><?= e($ui['nav_contact']) ?></a></li>
          <li><a class="nav-admin" href="<?= e(url('admin')) ?>" rel="nofollow" title="<?= e($ui['nav_admin']) ?>" aria-label="<?= e($ui['nav_admin']) ?>"><?= icon('gear') ?><span><?= e($ui['nav_admin']) ?></span></a></li>
        </ul>
      </nav>
      <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Ouvrir le menu"><span></span></button>
    </div>
  </header>

  <main id="contenu"><?= $content ?></main>

  <?= partial('footer', ['c' => $c]) ?>

  <button class="to-top" type="button" aria-label="Revenir en haut de page"><?= icon('up') ?></button>

  <nav class="mobile-bar" aria-label="Contact rapide">
    <a href="tel:<?= e(tel_href($s['phone'])) ?>"><?= icon('phone') ?> <?= e($ui['mobile_call']) ?></a>
    <a href="<?= e(url()) ?>#contact"><?= icon('mail') ?> <?= e($ui['mobile_write']) ?></a>
  </nav>

  <script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
