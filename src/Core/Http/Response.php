<?php

namespace Niang\Core\Http;

use Niang\Core\Cookie;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Session;

final class Response
{
    /**
     * Types servis « inline » par file() : un fichier envoyé par un visiteur (HTML, SVG, XML...)
     * affiché tel quel dans le navigateur exécuterait son JavaScript sur le domaine du site. Tout
     * autre type est proposé en téléchargement.
     */
    private const INLINE_SAFE = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif',
        'application/pdf', 'text/plain', 'audio/mpeg', 'video/mp4',
    ];

    private int $status = 200;
    private array $headers = [];
    private string $content = '';

    /** @var array<string, array{value: string|null, minutes: int}> posés à send() ; value null = suppression */
    private array $cookies = [];

    /** Fichier envoyé depuis le disque à send(), sans être chargé en mémoire (download(), file()). */
    private ?string $filePath = null;

    /** @var (\Closure(): void)|null écrit le corps directement à send() (stream()) */
    private ?\Closure $streamer = null;

    public function status(int $code): static
    {
        $this->status = $code;
        return $this;
    }

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Pose un cookie chiffré (voir Cookie) avec la réponse, plutôt qu'immédiatement : il n'est
     * envoyé que si cette réponse l'est — et reste visible dans les tests via getCookies().
     */
    public function cookie(string $name, string $value, int $minutes = 60): static
    {
        $this->cookies[$name] = ['value' => $value, 'minutes' => $minutes];
        return $this;
    }

    public function withoutCookie(string $name): static
    {
        $this->cookies[$name] = ['value' => null, 'minutes' => 0];
        return $this;
    }

    /** @return array<string, array{value: string|null, minutes: int}> */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function content(string $content): static
    {
        $this->content = $content;
        // Remplacer le contenu remplace aussi un fichier ou un flux (ex. réponse vidée pour HEAD).
        $this->filePath = null;
        $this->streamer = null;

        return $this;
    }

    public static function html(string $html, int $status = 200): static
    {
        return (new static())
            ->status($status)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->content($html);
    }

    public static function json(mixed $data, int $status = 200): static
    {
        return (new static())
            ->status($status)
            ->header('Content-Type', 'application/json; charset=utf-8')
            ->content(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function redirect(string $to, int $status = 302): static
    {
        return (new static())
            ->status($status)
            ->header('Location', $to);
    }

    /**
     * Propose un fichier en téléchargement (Content-Disposition: attachment), lu depuis le disque à
     * l'envoi. $name est le nom proposé au visiteur (défaut : celui du fichier), accents compris.
     *
     * @throws NotFoundException si le fichier n'existe pas
     */
    public static function download(string $path, ?string $name = null, array $headers = []): static
    {
        return self::fromFile($path, $name, 'attachment', $headers);
    }

    /**
     * Affiche un fichier dans le navigateur (images, PDF, texte...) — pour servir un upload rangé
     * dans storage/app/ par une route qui vérifie les droits. Un type qui pourrait exécuter du
     * JavaScript (HTML, SVG...) est proposé en téléchargement à la place, jamais affiché.
     */
    public static function file(string $path, ?string $name = null, array $headers = []): static
    {
        return self::fromFile($path, $name, 'inline', $headers);
    }

    /**
     * Réponse écrite au fil de l'eau par $callback (echo, fwrite sur php://output...) : export CSV
     * volumineux, flux d'événements... Ni compressée, ni modifiée par la barre de debug.
     */
    public static function stream(\Closure $callback, int $status = 200, array $headers = []): static
    {
        $response = (new static())->status($status);
        $response->streamer = $callback;

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }

    /** Corps lu depuis un fichier ou écrit par un flux : ni compressé ni modifié après coup. */
    public function isStreamed(): bool
    {
        return $this->filePath !== null || $this->streamer !== null;
    }

    private static function fromFile(string $path, ?string $name, string $disposition, array $headers): static
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new NotFoundException();
        }

        $mime = function_exists('mime_content_type') ? (mime_content_type($path) ?: 'application/octet-stream') : 'application/octet-stream';

        if ($disposition === 'inline' && !in_array($mime, self::INLINE_SAFE, true)) {
            $disposition = 'attachment';
        }

        $response = (new static())
            ->header('Content-Type', $mime . (str_starts_with($mime, 'text/') ? '; charset=utf-8' : ''))
            ->header('Content-Length', (string) filesize($path))
            ->header('Content-Disposition', self::disposition($disposition, $name ?? basename($path)))
            ->header('X-Content-Type-Options', 'nosniff');

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        $response->filePath = $path;

        return $response;
    }

    /** RFC 6266 : un nom ASCII de repli, et le nom exact en UTF-8 (filename*) ; guillemets et retours à la ligne neutralisés. */
    private static function disposition(string $type, string $name): string
    {
        $name = str_replace(["\r", "\n", '"', '\\', '/'], ' ', $name);
        $ascii = preg_replace('/[^\x20-\x7E]/u', '_', $name) ?? 'fichier';

        return sprintf('%s; filename="%s"; filename*=UTF-8\'\'%s', $type, $ascii, rawurlencode($name));
    }

    /** Flashe une donnée visible lors de la prochaine requête (ex: message après redirection). */
    public function with(string $key, mixed $value): static
    {
        Session::flash($key, $value);
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /** Le corps de la réponse ; pour un fichier ou un flux, le lit ou l'exécute (tests, pont PSR-7). */
    public function getContent(): string
    {
        if ($this->filePath !== null) {
            return (string) file_get_contents($this->filePath);
        }

        if ($this->streamer !== null) {
            ob_start();
            try {
                ($this->streamer)();
            } finally {
                $output = (string) ob_get_clean();
            }

            return $output;
        }

        return $this->content;
    }

    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    /** @return array<string, string> utilisé par Psr7Bridge pour transmettre tous les en-têtes lors d'une conversion vers PSR-7. */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $key => $value) {
                header("$key: $value");
            }
            foreach ($this->cookies as $name => $cookie) {
                $cookie['value'] === null ? Cookie::forget($name) : Cookie::set($name, $cookie['value'], $cookie['minutes']);
            }
        }

        if ($this->filePath !== null) {
            readfile($this->filePath);
            return;
        }

        if ($this->streamer !== null) {
            ($this->streamer)();
            return;
        }

        echo $this->content;
    }
}
