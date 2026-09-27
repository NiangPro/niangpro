<?php

declare(strict_types=1);

namespace Niang\Core\Http;

/**
 * Client HTTP minimal, sans extension (flux HTTP de PHP), pour les appels sortants du framework :
 * webhooks, OAuth. http et https uniquement, redirections non suivies, délai d'attente borné.
 * Une réponse 4xx/5xx est renvoyée normalement (status) ; seule une connexion impossible lève une
 * exception.
 *
 * @experimental destiné d'abord aux besoins internes (webhooks, OAuth).
 */
final class Client
{
    /**
     * @param array<string, string> $headers
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function request(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 10): array
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException("URL invalide (http ou https attendu) : « $url ».");
        }

        $lines = [];
        foreach ($headers + ['User-Agent' => 'NiangPro'] as $name => $value) {
            $lines[] = str_replace(["\r", "\n"], '', "$name: $value");
        }

        $context = stream_context_create(['http' => array_filter([
            'method' => strtoupper($method),
            'header' => implode("\r\n", $lines),
            'content' => $body,
            'timeout' => $timeout,
            'follow_location' => 0,
            'ignore_errors' => true,
        ], fn ($value) => $value !== null)]);

        $stream = @fopen($url, 'rb', false, $context);

        if ($stream === false) {
            throw new \RuntimeException("$url injoignable.");
        }

        $meta = stream_get_meta_data($stream);
        $content = (string) stream_get_contents($stream);
        fclose($stream);

        return ['status' => self::status($meta['wrapper_data'] ?? []), 'headers' => self::headers($meta['wrapper_data'] ?? []), 'body' => $content];
    }

    /**
     * @param array<string, string> $headers
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function postForm(string $url, array $fields, array $headers = []): array
    {
        return self::request('POST', $url, $headers + ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query($fields));
    }

    /**
     * @param array<string, string> $headers
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function postJson(string $url, mixed $data, array $headers = []): array
    {
        return self::request('POST', $url, $headers + ['Content-Type' => 'application/json'], json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    private static function status(mixed $raw): int
    {
        $first = is_array($raw) && is_string($raw[0] ?? null) ? $raw[0] : '';

        return preg_match('#^HTTP/\S+\s+(\d{3})#', $first, $matches) === 1 ? (int) $matches[1] : 0;
    }

    /** @return array<string, string> noms en minuscules */
    private static function headers(mixed $raw): array
    {
        $headers = [];

        foreach (is_array($raw) ? $raw : [] as $line) {
            if (is_string($line) && str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        return $headers;
    }
}
