<?php
/** @var ?string $status  @var string $token  @var string[] $missions  @var array $ui libellés (Textes communs) */
$messages = [
    'ok'     => ['ok', $ui['msg_ok']],
    'erreur' => ['ko', $ui['msg_error']],
    'limite' => ['ko', $ui['msg_limit']],
];
[$statusClass, $statusText] = $messages[$status ?? ''] ?? ['', ''];
// Messages affichés par le script du formulaire (public/js/main.js).
$jsMessages = array_intersect_key($ui, array_flip(['msg_ok', 'msg_error', 'msg_limit', 'msg_check', 'msg_sending', 'msg_network', 'err_required', 'err_consent', 'err_email', 'err_phone']));
?>
<form class="reveal reveal-delay-1" id="contact-form" method="post" action="<?= e(url('contact')) ?>" data-messages="<?= e(json_encode($jsMessages, JSON_UNESCAPED_UNICODE)) ?>" novalidate>
  <div class="form-grid">
    <div class="field">
      <label for="nom"><?= e($ui['form_name']) ?> <span class="req">*</span></label>
      <input id="nom" name="nom" type="text" autocomplete="name" maxlength="120" required>
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field">
      <label for="entreprise"><?= e($ui['form_company']) ?></label>
      <input id="entreprise" name="entreprise" type="text" autocomplete="organization" maxlength="160">
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field">
      <label for="email"><?= e($ui['form_email']) ?> <span class="req">*</span></label>
      <input id="email" name="email" type="email" autocomplete="email" maxlength="190" required>
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field">
      <label for="telephone"><?= e($ui['form_phone']) ?></label>
      <input id="telephone" name="telephone" type="tel" autocomplete="tel" maxlength="30">
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field full">
      <label for="mission"><?= e($ui['form_mission']) ?> <span class="req">*</span></label>
      <select id="mission" name="mission" required>
        <option value=""><?= e($ui['form_mission_placeholder']) ?></option>
<?php foreach ($missions as $m): ?>
        <option><?= e($m) ?></option>
<?php endforeach ?>
      </select>
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field full">
      <label for="message"><?= e($ui['form_message']) ?> <span class="req">*</span></label>
      <textarea id="message" name="message" maxlength="5000" required placeholder="<?= e($ui['form_message_placeholder']) ?>"></textarea>
      <span class="error" aria-live="polite"></span>
    </div>
    <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label class="consent">
      <input type="checkbox" name="rgpd" value="1" required>
      <span><?= e($ui['form_consent']) ?> <a href="<?= e(url('mentions-legales')) ?>"><?= e($ui['form_consent_link']) ?></a><span class="error" aria-live="polite"></span></span>
    </label>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit"><?= e($ui['form_submit']) ?> <?= icon('arrow') ?></button>
      <p class="form-status<?= $statusClass ? ' ' . $statusClass : '' ?>" role="status"><?= e($statusText) ?></p>
    </div>
  </div>
</form>
