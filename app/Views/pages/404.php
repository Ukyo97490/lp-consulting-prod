<?= partial('page-hero', [
    'image'   => 'img/bandeau-chantiers.jpg',
    'crumbs'  => ['Accueil' => url(), 'Page introuvable' => null],
    'eyebrow' => 'Erreur 404',
    'title'   => 'Cette page n’existe pas ou a été déplacée',
]) ?>

<section class="section">
  <div class="wrap">
    <p>Le lien suivi est peut-être incorrect. Vous pouvez revenir à l’accueil ou consulter nos missions.</p>
    <p style="margin-top:24px"><a class="btn btn-primary" href="<?= e(url()) ?>">Retour à l’accueil <?= icon('arrow') ?></a></p>
  </div>
</section>
