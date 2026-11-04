<?php /** Mise en page de l'administration connectée. @var string $content  @var string $csrf */ ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= e($csrf) ?>">
  <meta name="base-path" content="<?= e(App\Core\Url::base()) ?>">
  <title>Administration | LP CONSULTING</title>
  <link rel="icon" type="image/png" href="<?= e(url('img/logo-mark.png')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/admin/admin.css')) ?>">
</head>
<body>
<?= $content ?>
<script src="<?= e(asset('assets/admin/admin.js')) ?>"></script>
</body>
</html>
