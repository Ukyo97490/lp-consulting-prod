<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;
use App\Core\Url;
use App\Models\Message;
use Throwable;

/**
 * Formulaire de contact, sans cookie : jeton signé (âge minimal et maximal), champ piège,
 * limite de demandes par adresse IP. Chaque demande est enregistrée avant l'envoi de l'e-mail.
 */
final class ContactForm
{
    public const MISSIONS = ['Études techniques / appel d’offres', 'Préparation de chantier', 'Direction de travaux / pilotage', 'Autre demande'];
    public const MAX_PER_HOUR = 5;
    private const MIN_SECONDS = 3;
    private const MAX_SECONDS = 86400;

    public static function token(): string
    {
        $ts = (string) time();
        return $ts . '.' . self::sign($ts);
    }

    /** @return array{status: string, errors: array<string, string>} status : ok | erreur | limite */
    public static function submit(array $in, string $ip, string $userAgent): array
    {
        $v = fn(string $k, int $max) => mb_substr(trim(str_replace("\0", '', (string) ($in[$k] ?? ''))), 0, $max);
        $d = [
            'name'    => $v('nom', 120),
            'company' => $v('entreprise', 160),
            'email'   => $v('email', 190),
            'phone'   => $v('telephone', 30),
            'mission' => $v('mission', 120),
            'message' => $v('message', 5000),
        ];

        // Robot probable : on répond « ok » sans rien enregistrer pour ne pas l'aider à s'adapter.
        if (($in['website'] ?? '') !== '' || !self::tokenValid((string) ($in['token'] ?? ''))) {
            return ['status' => 'ok', 'errors' => []];
        }

        $errors = [];
        foreach (['name' => 'nom', 'email' => 'email', 'mission' => 'mission', 'message' => 'message'] as $k => $field) {
            if ($d[$k] === '') $errors[$field] = 'Ce champ est requis.';
        }
        if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Adresse e-mail invalide.';
        if ($d['phone'] !== '' && !preg_match('/^[0-9 +().-]{8,20}$/', $d['phone'])) $errors['telephone'] = 'Numéro de téléphone invalide.';
        if ($d['mission'] !== '' && !in_array($d['mission'], self::MISSIONS, true)) $errors['mission'] = 'Choix invalide.';
        if (empty($in['rgpd'])) $errors['rgpd'] = 'Merci d’accepter pour envoyer votre demande.';
        if ($errors) return ['status' => 'erreur', 'errors' => $errors];

        if (Message::countRecentFromIp($ip, 60) >= self::MAX_PER_HOUR) return ['status' => 'limite', 'errors' => []];

        $id = Message::create($d + ['ip' => $ip, 'user_agent' => $userAgent]);
        self::notify($id, $d);
        return ['status' => 'ok', 'errors' => []];
    }

    /** Un échec d'envoi n'est pas bloquant : la demande reste consultable dans l'administration. */
    private static function notify(int $id, array $d): void
    {
        try {
            $site = SiteContent::content()['site'];
            $body = "Nouvelle demande reçue depuis le site {$site['name']}\n\n"
                . "Nom : {$d['name']}\nEntreprise : {$d['company']}\nE-mail : {$d['email']}\nTéléphone : {$d['phone']}\nMission : {$d['mission']}\n\n"
                . $d['message'] . "\n\n--\nDemande n° $id, également consultable dans l’administration : " . Url::absolute('admin#messages') . "\n";
            (new Mailer())->send(
                Config::get('mail.to') ?: $site['email'],
                'Demande de contact : ' . $d['mission'] . ' (' . $d['name'] . ')',
                $body,
                $d['email'],
                $d['name']
            );
            Message::markMailed($id);
        } catch (Throwable $e) {
            Logger::error('Envoi de la demande #' . $id . ' impossible : ' . $e->getMessage());
        }
    }

    private static function tokenValid(string $token): bool
    {
        [$ts, $sig] = array_pad(explode('.', $token, 2), 2, '');
        if (!ctype_digit($ts) || !hash_equals(self::sign($ts), $sig)) return false;
        $age = time() - (int) $ts;
        return $age >= self::MIN_SECONDS && $age <= self::MAX_SECONDS;
    }

    private static function sign(string $ts): string
    {
        return hash_hmac('sha256', 'contact|' . $ts, (string) Config::get('app.key'));
    }
}
