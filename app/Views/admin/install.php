<?php /** @var string $state form|installed|done  @var string[] $log  @var string $error  @var string $token  @var string $name  @var string $email */ ?>
<h1>Installation</h1>
<?php if ($state === 'done'): ?>
<p>Le site est déjà installé. Désactivez l’installation dans <code>config/config.php</code> (<code>install.enabled</code> à <code>false</code>).</p>
<a class="btn-main" href="<?= e(url('admin/login')) ?>">Administration</a>

<?php elseif ($state === 'installed'): ?>
<ul class="install-log">
<?php foreach ($log as $line): ?>
  <li><?= e($line) ?></li>
<?php endforeach ?>
  <li>Compte administrateur créé.</li>
</ul>
<p class="login-error" role="alert"><b>Important :</b> remettez maintenant <code>install.enabled</code> à <code>false</code> dans <code>config/config.php</code>.</p>
<a class="btn-main" href="<?= e(url('admin/login')) ?>">Se connecter</a>

<?php else: ?>
<?php if ($error !== ''): ?>
<p class="login-error" role="alert"><?= e($error) ?></p>
<?php endif ?>
<form method="post" action="<?= e(url('install')) ?>">
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <label for="name">Nom</label>
  <input id="name" name="name" required value="<?= e($name) ?>">
  <label for="email">E-mail de connexion</label>
  <input id="email" name="email" type="email" required value="<?= e($email) ?>">
  <label for="password">Mot de passe (12 caractères minimum, lettres et chiffres)</label>
  <input id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
  <button class="btn-main" type="submit">Installer</button>
</form>
<?php endif ?>
