<?php

namespace Niang\Core\Http;

class Request
{
    public function __construct(
        public string $method,
        public string $uri,
        public array $query = [],
        public array $body = [],
        public array $server = [],
        public array $headers = [],
        public array $params = [],
    ) {
    }

    /** Construit la requête depuis les superglobales — utilisé en production par Application::run(). */
    public static function capture(): static
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $server = $_SERVER;
        $headers = self::captureHeaders($server);
        $body = self::captureBody($method, $headers, $server);

        return new static($method, $uri, $_GET, $body, $server, $headers);
    }

    /** Construit une requête explicite — utilisé par le client de test (Niang\Core\Testing\TestCase). */
    public static function create(string $method, string $uri, array $data = [], array $server = [], array $headers = []): static
    {
        $method = strtoupper($method);
        $query = $method === 'GET' ? $data : [];
        $body = $method === 'GET' ? [] : $data;

        return new static($method, $uri, $query, $body, $server, $headers);
    }

    private static function captureHeaders(array $server): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }

        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    private static function captureBody(string $method, array $headers, array $server): array
    {
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $contentType = $headers['Content-Type'] ?? $server['CONTENT_TYPE'] ?? '';

            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                return json_decode($raw, true) ?: [];
            }

            return $_POST;
        }

        return [];
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept', '');
        return str_contains($accept, 'application/json')
            || str_contains($this->header('Content-Type', ''), 'application/json');
    }
}
