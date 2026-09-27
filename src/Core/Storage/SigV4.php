<?php

declare(strict_types=1);

namespace Niang\Core\Storage;

/**
 * Signature AWS Signature Version 4, pour S3 et les services compatibles (Cloudflare R2, MinIO,
 * Wasabi, Scaleway...). Écrite à la main, sans SDK : https://docs.aws.amazon.com/AmazonS3/latest/API/sig-v4-authenticating-requests.html
 * Vérifiée contre les exemples publiés par AWS (voir tests/Unit/Storage/SigV4Test.php).
 *
 * @internal utilisée par S3Client
 */
final class SigV4
{
    public const UNSIGNED_PAYLOAD = 'UNSIGNED-PAYLOAD';

    public function __construct(
        private string $key,
        private string $secret,
        private string $region,
        private string $service = 's3',
    ) {
    }

    /**
     * En-têtes à ajouter à la requête (Authorization, x-amz-date, x-amz-content-sha256).
     *
     * @param array<string, string> $headers en-têtes signés en plus de host (ex. Content-Type, Range)
     * @return array<string, string>
     */
    public function sign(string $method, string $url, array $headers, string $payloadHash, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $amzDate = $now->format('Ymd\THis\Z');
        $date = $now->format('Ymd');

        $parts = parse_url($url);
        $host = (string) ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $headers = ['host' => $host, 'x-amz-content-sha256' => $payloadHash, 'x-amz-date' => $amzDate]
            + array_change_key_case($headers, CASE_LOWER);
        ksort($headers);

        $signedHeaders = implode(';', array_keys($headers));
        $canonicalHeaders = '';
        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name . ':' . trim((string) preg_replace('/\s+/', ' ', $value)) . "\n";
        }

        $canonicalRequest = implode("\n", [
            strtoupper($method),
            self::canonicalPath((string) ($parts['path'] ?? '/')),
            self::canonicalQuery((string) ($parts['query'] ?? '')),
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $scope = "$date/{$this->region}/{$this->service}/aws4_request";
        $signature = $this->signature($date, "AWS4-HMAC-SHA256\n$amzDate\n$scope\n" . hash('sha256', $canonicalRequest));

        unset($headers['host']);

        return $headers + [
            'authorization' => "AWS4-HMAC-SHA256 Credential={$this->key}/$scope, SignedHeaders=$signedHeaders, Signature=$signature",
        ];
    }

    /** URL pré-signée (paramètres X-Amz-* dans la requête), valable $seconds secondes : partageable sans identifiants. */
    public function presign(string $method, string $url, int $seconds, ?\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $amzDate = $now->format('Ymd\THis\Z');
        $date = $now->format('Ymd');
        $scope = "$date/{$this->region}/{$this->service}/aws4_request";

        $parts = parse_url($url);
        $host = (string) ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
        parse_str((string) ($parts['query'] ?? ''), $query);
        $query += [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => "{$this->key}/$scope",
            'X-Amz-Date' => $amzDate,
            'X-Amz-Expires' => (string) max(1, min($seconds, 604800)),
            'X-Amz-SignedHeaders' => 'host',
        ];
        $queryString = self::canonicalQuery(http_build_query($query, '', '&', PHP_QUERY_RFC3986));

        $canonicalRequest = implode("\n", [
            strtoupper($method),
            self::canonicalPath((string) ($parts['path'] ?? '/')),
            $queryString,
            "host:$host\n",
            'host',
            self::UNSIGNED_PAYLOAD,
        ]);

        $signature = $this->signature($date, "AWS4-HMAC-SHA256\n$amzDate\n$scope\n" . hash('sha256', $canonicalRequest));
        $base = ($parts['scheme'] ?? 'https') . '://' . $host . ($parts['path'] ?? '/');

        return "$base?$queryString&X-Amz-Signature=$signature";
    }

    private function signature(string $date, string $stringToSign): string
    {
        $key = hash_hmac('sha256', $date, 'AWS4' . $this->secret, true);
        $key = hash_hmac('sha256', $this->region, $key, true);
        $key = hash_hmac('sha256', $this->service, $key, true);
        $key = hash_hmac('sha256', 'aws4_request', $key, true);

        return hash_hmac('sha256', $stringToSign, $key);
    }

    /** Chaque segment encodé une fois (RFC 3986), les « / » conservés : règle propre à S3. */
    private static function canonicalPath(string $path): string
    {
        return implode('/', array_map(fn (string $segment) => rawurlencode(rawurldecode($segment)), explode('/', $path === '' ? '/' : $path)));
    }

    private static function canonicalQuery(string $query): string
    {
        if ($query === '') {
            return '';
        }

        $pairs = [];
        foreach (explode('&', $query) as $pair) {
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $pairs[] = [rawurlencode(rawurldecode($name)), rawurlencode(rawurldecode($value))];
        }

        usort($pairs, fn (array $a, array $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return implode('&', array_map(fn (array $p) => $p[0] . '=' . $p[1], $pairs));
    }
}
