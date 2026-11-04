<?php
/** @var array $c */
use App\Services\ContentSchema;

$s = $c['site'];
?>
<footer class="site-footer" id="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-brand">
      <img src="<?= e(url('img/logo-blanc.png')) ?>" alt="<?= e($s['name']) ?>" width="640" height="297" loading="lazy">
      <p><?= e($s['footer_text']) ?></p>
    </div>
    <div>
      <h4>Missions</h4>
      <ul>
<?php foreach (ContentSchema::MISSIONS as $slug): ?>
        <li><a href="<?= e(url($slug)) ?>"><?= e($c['missions'][$slug]['short']) ?></a></li>
<?php endforeach ?>
      </ul>
    </div>
    <div>
      <h4>Le cabinet</h4>
      <ul>
        <li><a href="<?= e(url()) ?>#presentation">Présentation</a></li>
        <li><a href="<?= e(url('references')) ?>">Références</a></li>
        <li><a href="<?= e(url()) ?>#contact">Contact</a></li>
        <li><a href="<?= e(url('mentions-legales')) ?>">Mentions légales</a></li>
      </ul>
    </div>
    <div>
      <h4>Contact</h4>
      <ul>
        <li><a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a></li>
        <li><a href="tel:<?= e(tel_href($s['phone'])) ?>"><?= e($s['phone']) ?></a></li>
        <li><?= e($s['zone']) ?></li>
      </ul>
    </div>
  </div>
  <div class="wrap footer-bottom">
    <span>© <?= date('Y') ?> <?= e($s['name']) ?>. Tous droits réservés.</span>
    <span><a href="<?= e(url('mentions-legales')) ?>">Mentions légales</a> · <?= e(preg_replace('#^https?://(www\.)?#', '', (string) $s['domain'])) ?></span>
  </div>
</footer>
