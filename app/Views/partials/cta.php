<?php /** @var string $title @var string $text @var string $button */ ?>
<section class="cta on-dark">
  <div class="wrap cta-inner reveal">
    <div>
      <h2><?= e($title) ?></h2>
      <p><?= e($text) ?></p>
    </div>
    <a class="btn btn-sky" href="<?= e(url()) ?>#contact"><?= e($button) ?> <?= icon('arrow') ?></a>
  </div>
</section>
