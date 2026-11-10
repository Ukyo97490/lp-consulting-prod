<?php
/** Page d'erreur autonome (ne dépend pas de la base de données). @var ?string $detail  @var ?string $message */
$message ??= null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Service momentanément indisponible | LP CONSULTING</title>
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body>
  <main class="section">
    <div class="wrap">
      <img src="<?= e(url('img/logo.png')) ?>" alt="LP CONSULTING" width="240" height="112" style="height:auto">
      <h1 style="margin-top:32px"><?= e($message ?: 'Service momentanément indisponible') ?></h1>
      <p>Merci de réessayer dans quelques instants. Vous pouvez aussi nous écrire à <a href="mailto:contact@lp-consulting.re">contact@lp-consulting.re</a>.</p>
      <p style="margin-top:24px"><a class="btn btn-primary" href="<?= e(url()) ?>">Retour à l’accueil</a></p>
<?php if (!empty($detail)): ?>
      <pre style="margin-top:32px;white-space:pre-wrap;font-size:.8rem;background:#f5f9fb;padding:16px"><?= e($detail) ?></pre>
<?php endif ?>
    </div>
  </main>
</body>
</html>
