<?php
/** @var ?string $status  @var string $token  @var string[] $missions */
$messages = [
    'ok'     => ['ok', 'Merci, votre demande a bien été envoyée. Nous vous répondons rapidement.'],
    'erreur' => ['ko', 'Votre demande n’a pas pu être envoyée. Vérifiez les champs ou écrivez-nous directement par e-mail.'],
    'limite' => ['ko', 'Trop de demandes envoyées depuis votre connexion. Merci de réessayer plus tard.'],
];
[$statusClass, $statusText] = $messages[$status ?? ''] ?? ['', ''];
?>
<form class="reveal reveal-delay-1" id="contact-form" method="post" action="<?= e(url('contact')) ?>" novalidate>
  <div class="form-grid">
    <div class="field">
      <label for="nom">Nom et prénom <span class="req">*</span></label>
      <input id="nom" name="nom" type="text" autocomplete="name" maxlength="120" required>
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field">
      <label for="entreprise">Entreprise</label>
      <input id="entreprise" name="entreprise" type="text" autocomplete="organization" maxlength="160">
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field">
      <label for="email">E-mail <span class="req">*</span></label>
      <input id="email" name="email" type="email" autocomplete="email" maxlength="190" required>
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field">
      <label for="telephone">Téléphone</label>
      <input id="telephone" name="telephone" type="tel" autocomplete="tel" maxlength="30">
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field full">
      <label for="mission">Type de mission <span class="req">*</span></label>
      <select id="mission" name="mission" required>
        <option value="">Sélectionnez…</option>
<?php foreach ($missions as $m): ?>
        <option><?= e($m) ?></option>
<?php endforeach ?>
      </select>
      <span class="error" aria-live="polite"></span>
    </div>
    <div class="field full">
      <label for="message">Votre projet <span class="req">*</span></label>
      <textarea id="message" name="message" maxlength="5000" required placeholder="Nature de l’opération, localisation, échéances…"></textarea>
      <span class="error" aria-live="polite"></span>
    </div>
    <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label class="consent">
      <input type="checkbox" name="rgpd" value="1" required>
      <span>J’accepte que les informations saisies soient utilisées pour répondre à ma demande. <a href="<?= e(url('mentions-legales')) ?>">En savoir plus</a><span class="error" aria-live="polite"></span></span>
    </label>
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Envoyer ma demande <?= icon('arrow') ?></button>
      <p class="form-status<?= $statusClass ? ' ' . $statusClass : '' ?>" role="status"><?= e($statusText) ?></p>
    </div>
  </div>
</form>
