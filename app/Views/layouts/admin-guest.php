<?php /** Mise en page des écrans hors connexion (connexion, installation). @var string $content */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Administration | LP CONSULTING</title>
  <link rel="icon" type="image/png" href="<?= e(url('img/logo-mark.png')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/admin/admin.css')) ?>">
</head>
<body class="login-page">
  <main class="login">
    <div class="login-card">
      <img src="<?= e(url('img/logo.png')) ?>" alt="LP CONSULTING" width="220">
      <?= $content ?>
    </div>
  </main>
</body>
</html>
