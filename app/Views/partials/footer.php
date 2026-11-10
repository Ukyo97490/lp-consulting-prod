<?php
/** @var array $c */
use App\Services\ContentSchema;

$s = $c['site'];
$ui = $c['ui'];
?>
<footer class="site-footer" id="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <?= picture('img/logo-blanc.png', $s['name'], ['sizes' => '220px']) ?>
      <p><?= e($s['footer_text']) ?></p>
    </div>
    <div>
      <h2><?= e($ui['footer_missions']) ?></h2>
      <ul>
<?php foreach (ContentSchema::MISSIONS as $slug): ?>
        <li><a href="<?= e(url($slug)) ?>"><?= e($c['missions'][$slug]['short']) ?></a></li>
<?php endforeach ?>
      </ul>
    </div>
    <div>
      <h2><?= e($ui['footer_cabinet']) ?></h2>
      <ul>
        <li><a href="<?= e(url()) ?>#presentation"><?= e($ui['footer_presentation']) ?></a></li>
        <li><a href="<?= e(url('references')) ?>"><?= e($ui['footer_references']) ?></a></li>
        <li><a href="<?= e(url()) ?>#contact"><?= e($ui['footer_contact_link']) ?></a></li>
        <li><a href="<?= e(url('mentions-legales')) ?>"><?= e($ui['footer_legal']) ?></a></li>
      </ul>
    </div>
    <div>
      <h2><?= e($ui['footer_contact']) ?></h2>
      <ul>
        <li><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></li>
        <li><a href="tel:<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a></li>
        <li><?= e($s['zone']) ?></li>
      </ul>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <span>© <?= date('Y') ?> <?= e($s['name']) ?>. <?= e($ui['footer_rights']) ?></span>
    <span><a href="<?= e(url('mentions-legales')) ?>"><?= e($ui['footer_legal']) ?></a> · <?= e(preg_replace('#^https?://(www\.)?#', '', (string) $s['domain'])) ?></span>
  </div>
</footer>
