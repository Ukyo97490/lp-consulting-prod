<?php /** @var string $error  @var string $email  @var string $csrf */ ?>
<h1>Administration</h1>
<?php if ($error !== ''): ?>
<p class="login-error" role="alert"><?= e($error) ?></p>
<?php endif ?>
<form method="post" action="<?= e(url('admin/login')) ?>">
  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
  <label for="email">E-mail</label>
  <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>" autofocus>
  <label for="password">Mot de passe</label>
  <input id="password" name="password" type="password" autocomplete="current-password" required>
  <button class="btn-main" type="submit">Se connecter</button>
</form>
<a class="login-back" href="<?= e(url()) ?>">← Retour au site</a>
