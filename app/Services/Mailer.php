<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use RuntimeException;

/** Envoi d'e-mails texte : client SMTP minimal (SSL ou STARTTLS, AUTH LOGIN) ou fonction mail() de PHP. */
final class Mailer
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = (array) Config::get('mail', []);
    }

    /** Lève une exception en cas d'échec. */
    public function send(string $to, string $subject, string $body, ?string $replyTo = null, string $replyName = ''): void
    {
        $from = self::clean((string) ($this->cfg['from'] ?? ''));
        $fromName = self::clean((string) ($this->cfg['from_name'] ?? ''));
        $to = self::clean($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Adresse d’expédition ou de destination invalide.');
        }
        $subject = self::encode(self::clean($subject));
        $headers = [
            'Date: ' . date('r'),
            'From: ' . ($fromName !== '' ? self::encode($fromName) . ' ' : '') . '<' . $from . '>',
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . substr((string) strrchr($from, '@'), 1) . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers[] = 'Reply-To: ' . ($replyName !== '' ? self::encode(self::clean($replyName)) . ' ' : '') . '<' . self::clean($replyTo) . '>';
        }
        $encoded = rtrim(chunk_split(base64_encode(str_replace(["\r\n", "\r"], "\n", $body)), 76, "\r\n"));

        if (($this->cfg['transport'] ?? 'smtp') === 'mail') {
            if (!mail($to, $subject, $encoded, implode("\r\n", $headers), '-f' . $from)) throw new RuntimeException('mail() a échoué.');
            return;
        }
        $message = implode("\r\n", array_merge($headers, ['To: <' . $to . '>', 'Subject: ' . $subject])) . "\r\n\r\n" . $encoded;
        $this->smtp($from, $to, $message);
    }

    private function smtp(string $from, string $to, string $data): void
    {
        $c = $this->cfg;
        $enc = strtolower((string) ($c['encryption'] ?? ''));
        $timeout = (int) ($c['timeout'] ?? 15);
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $fp = @stream_socket_client(($enc === 'ssl' ? 'ssl://' : '') . $c['host'] . ':' . (int) $c['port'], $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) throw new RuntimeException("Connexion SMTP impossible : $errstr ($errno)");
        stream_set_timeout($fp, $timeout);

        try {
            $this->expect($fp, [220]);
            $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
            $this->command($fp, $ehlo, [250]);
            if ($enc === 'tls') {
                $this->command($fp, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('STARTTLS a échoué.');
                }
                $this->command($fp, $ehlo, [250]);
            }
            if (!empty($c['username'])) {
                $this->command($fp, 'AUTH LOGIN', [334]);
                $this->command($fp, base64_encode((string) $c['username']), [334], true);
                $this->command($fp, base64_encode((string) $c['password']), [235], true);
            }
            $this->command($fp, 'MAIL FROM:<' . $from . '>', [250]);
            $this->command($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->command($fp, 'DATA', [354]);
            // Les lignes commençant par un point sont doublées (RFC 5321, 4.5.2).
            $this->command($fp, preg_replace('/^\./m', '..', $data) . "\r\n.", [250], true);
            $this->command($fp, 'QUIT', [221]);
        } finally {
            fclose($fp);
        }
    }

    /** @param resource $fp */
    private function command($fp, string $cmd, array $ok, bool $secret = false): void
    {
        fwrite($fp, $cmd . "\r\n");
        $this->expect($fp, $ok, $secret ? '[masqué]' : $cmd);
    }

    /** @param resource $fp */
    private function expect($fp, array $ok, string $cmd = 'connexion'): void
    {
        $resp = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        if (!in_array((int) substr($resp, 0, 3), $ok, true)) throw new RuntimeException("SMTP « $cmd » : " . trim($resp));
    }

    private static function clean(string $s): string
    {
        return trim(str_replace(["\r", "\n"], ' ', $s));
    }

    private static function encode(string $s): string
    {
        return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }
}
