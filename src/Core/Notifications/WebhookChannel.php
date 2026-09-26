<?php

namespace Niang\Core\Notifications;

use Niang\Core\Contracts\NotificationChannel;
use Niang\Core\Exceptions\NotificationException;
use Niang\Core\Notification;

/**
 * POST JSON de Notification::toWebhook() vers Notification::webhookUrl(), sans extension (flux HTTP
 * de PHP). Avec un secret, en-tête X-Niang-Signature: sha256=<HMAC du corps> pour que le
 * destinataire vérifie l'origine. Pas de redirection suivie ; une réponse hors 2xx lève une erreur
 * (et, via la file, le job est retenté).
 */
final class WebhookChannel implements NotificationChannel
{
    public const TIMEOUT_SECONDS = 10;

    public function send(array $notifiable, Notification $notification): void
    {
        $url = $notification->webhookUrl($notifiable);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new NotificationException("Canal webhook : URL invalide (http ou https attendu) : « $url ».");
        }

        $body = json_encode($notification->toWebhook($notifiable), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $headers = ['Content-Type: application/json', 'Accept: application/json', 'User-Agent: NiangPro-Webhook'];
        $secret = $notification->webhookSecret();

        if ($secret !== null && $secret !== '') {
            $headers[] = 'X-Niang-Signature: sha256=' . hash_hmac('sha256', $body, $secret);
        }

        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => self::TIMEOUT_SECONDS,
            'follow_location' => 0,
            'ignore_errors' => true,
        ]]);

        $stream = @fopen($url, 'rb', false, $context);

        if ($stream === false) {
            throw new NotificationException("Canal webhook : $url injoignable.");
        }

        // En-têtes de réponse lus sur le flux (ignore_errors : ouvert même pour un 4xx/5xx).
        $status = self::status(stream_get_meta_data($stream)['wrapper_data'] ?? []);
        fclose($stream);

        if ($status < 200 || $status >= 300) {
            throw new NotificationException("Canal webhook : $url a répondu $status.");
        }
    }

    private static function status(mixed $headers): int
    {
        $first = is_array($headers) && is_string($headers[0] ?? null) ? $headers[0] : '';

        return preg_match('#^HTTP/\S+\s+(\d{3})#', $first, $matches) === 1 ? (int) $matches[1] : 0;
    }
}
