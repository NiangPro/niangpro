<?php

namespace Niang\Core\Http;

use Niang\Core\Session;

final class Response
{
    private int $status = 200;
    private array $headers = [];
    private string $content = '';

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

    public function content(string $content): static
    {
        $this->content = $content;
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $key => $value) {
                header("$key: $value");
            }
        }

        echo $this->content;
    }
}
