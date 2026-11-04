<?php /** @var array $user  @var string $csrf */ ?>
<div class="layout">
  <aside class="side" id="side">
    <a class="side-brand" href="#tableau"><img src="<?= e(url('img/logo-blanc.png')) ?>" alt="LP CONSULTING"><span>Administration</span></a>
    <nav id="side-nav"></nav>
    <a class="side-site" href="<?= e(url()) ?>" target="_blank" rel="noopener">Voir le site</a>
  </aside>

  <div class="main">
    <header class="top">
      <button class="menu-btn" type="button" aria-label="Menu" data-menu>☰</button>
      <div class="demo-flag"><b>En ligne</b> Les modifications enregistrées sont publiées immédiatement.<span> Chaque version est conservée dans l’historique.</span></div>
      <div class="top-actions">
        <span class="user"><?= e($user['name'] ?: $user['email']) ?></span>
        <form method="post" action="<?= e(url('admin/logout')) ?>">
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
          <button class="btn-sec sm" type="submit">Déconnexion</button>
        </form>
      </div>
    </header>
    <div id="toast" class="toast" role="status" aria-live="polite"></div>
    <div class="page" id="view"><p class="intro">Chargement…</p></div>
  </div>
</div>
