<?php

declare(strict_types=1);

namespace Niang\Core\Storage;

use Niang\Core\Http\Client;

/**
 * Client S3 minimal (put, get, head, delete, URL pré-signée), signé en SigV4, sans SDK. Fonctionne avec
 * AWS S3 et les services compatibles : renseignez `endpoint` (ex. https://<compte>.r2.cloudflarestorage.com,
 * http://127.0.0.1:9000 pour MinIO) et `path_style` si le service n'accepte pas bucket.hôte.
 *
 * @internal utilisé par Niang\Core\Storage (FILESYSTEM_DISK=s3)
 */
final class S3Client
{
    private SigV4 $signer;
    private string $region;
    private string $bucket;
    private string $endpoint;
    private bool $pathStyle;

    /** @param array<string, mixed> $config lu dans config/filesystems.php (s3), vérifié ici */
    public function __construct(array $config)
    {
        foreach (['key', 'secret', 'region', 'bucket'] as $required) {
            if (!is_string($config[$required] ?? null) || $config[$required] === '') {
                throw new \Niang\Core\Exceptions\ConfigurationException("Disque s3 : « $required » manquant (config/filesystems.php, AWS_* dans .env).");
            }
        }

        $this->region = (string) $config['region'];
        $this->bucket = (string) $config['bucket'];
        $this->endpoint = rtrim(is_string($config['endpoint'] ?? null) ? $config['endpoint'] : '', '/');
        // Sans endpoint (AWS), le style « bucket.hôte » est le défaut ; avec un service compatible, le style chemin.
        $this->pathStyle = isset($config['path_style']) ? (bool) $config['path_style'] : $this->endpoint !== '';
        $this->signer = new SigV4((string) $config['key'], (string) $config['secret'], $this->region);
    }

    public function put(string $path, string $contents, ?string $contentType = null): bool
    {
        $headers = ['content-type' => $contentType ?? 'application/octet-stream'];
        $response = $this->send('PUT', $path, $headers, $contents);

        return $response['status'] === 200;
    }

    public function get(string $path): ?string
    {
        $response = $this->send('GET', $path);

        return $response['status'] === 200 ? $response['body'] : null;
    }

    /** Taille en octets, ou null si l'objet n'existe pas. */
    public function head(string $path): ?int
    {
        $response = $this->send('HEAD', $path);

        return $response['status'] === 200 ? (int) ($response['headers']['content-length'] ?? 0) : null;
    }

    public function delete(string $path): bool
    {
        // S3 répond 204 même si l'objet n'existait pas.
        return in_array($this->send('DELETE', $path)['status'], [200, 204], true);
    }

    public function presignedUrl(string $path, int $seconds): string
    {
        return $this->signer->presign('GET', $this->url($path), $seconds);
    }

    /** URL de l'objet (lisible sans signature seulement si le bucket ou l'objet est public). */
    public function url(string $path): string
    {
        $key = implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));

        if ($this->endpoint === '') {
            return $this->pathStyle
                ? "https://s3.{$this->region}.amazonaws.com/{$this->bucket}/$key"
                : "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/$key";
        }

        if ($this->pathStyle) {
            return "{$this->endpoint}/{$this->bucket}/$key";
        }

        $scheme = (string) parse_url($this->endpoint, PHP_URL_SCHEME);

        return $scheme . '://' . $this->bucket . '.' . substr($this->endpoint, strlen($scheme) + 3) . "/$key";
    }

    /**
     * @param array<string, string> $headers
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function send(string $method, string $path, array $headers = [], string $body = ''): array
    {
        $url = $this->url($path);
        $signed = $this->signer->sign($method, $url, $headers, hash('sha256', $body));
        $response = Client::request($method, $url, $signed, $method === 'PUT' ? $body : null, 30);

        if ($response['status'] === 403 || $response['status'] >= 500) {
            throw new \RuntimeException("S3 : $method $path a répondu {$response['status']} : " . substr(strip_tags($response['body']), 0, 300));
        }

        return $response;
    }
}
