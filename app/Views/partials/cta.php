<?php /** @var string $title @var string $text */ ?>
<section class="cta on-dark">
  <div class="wrap cta-inner reveal">
    <div>
      <h2><?= e($title) ?></h2>
      <p><?= e($text) ?></p>
    </div>
    <a class="btn btn-sky" href="<?= e(url()) ?>#contact">Contacter LP CONSULTING <?= icon('arrow') ?></a>
  </div>
</section>
