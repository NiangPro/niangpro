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
        /** @var array<string, UploadedFile|array> fichiers envoyés, par nom de champ (voir file()) */
        public array $files = [],
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

        return new static($method, $uri, $_GET, $body, $server, $headers, [], self::normalizeFiles($_FILES));
    }

    /** Construit une requête explicite — utilisé par le client de test (Niang\Core\Testing\TestCase). */
    public static function create(string $method, string $uri, array $data = [], array $server = [], array $headers = []): static
    {
        // Les UploadedFile (ex. UploadedFile::fake()) passés dans $data vont dans $files, comme le
        // ferait PHP avec $_FILES pour un vrai envoi multipart — jamais dans le corps.
        [$data, $files] = self::extractFiles($data);

        $method = strtoupper($method);

        // Une query string peut accompagner n'importe quelle méthode (ex: un POST vers une URL
        // signée) — capture() la lirait depuis $_GET indépendamment du corps ; ici on l'extrait
        // de $uri pour que $data reste dédiée au corps hors GET.
        $questionMark = strpos($uri, '?');
        $query = [];

        if ($questionMark !== false) {
            parse_str(substr($uri, $questionMark + 1), $query);
            $uri = substr($uri, 0, $questionMark);
        }

        $body = $method === 'GET' ? [] : $data;
        $query = $method === 'GET' ? array_merge($query, $data) : $query;

        return new static($method, $uri, $query, $body, $server, $headers, [], $files);
    }

    /**
     * $_FILES range un champ multiple (name="photos[]") « à l'envers » : $_FILES['photos']['name'][0].
     * On le remet à l'endroit — $files['photos'][0] est un UploadedFile — et on ignore les champs
     * laissés vides (UPLOAD_ERR_NO_FILE), pour que `required` voie un champ absent, pas une erreur.
     *
     * @return array<string, UploadedFile|array>
     */
    public static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $field => $spec) {
            $file = self::normalizeFileSpec($spec['name'] ?? null, $spec['tmp_name'] ?? null, $spec['type'] ?? null, $spec['error'] ?? null);

            if ($file !== null) {
                $normalized[$field] = $file;
            }
        }

        return $normalized;
    }

    private static function normalizeFileSpec(mixed $name, mixed $tmpName, mixed $type, mixed $error): UploadedFile|array|null
    {
        if (is_array($name)) {
            $files = [];

            foreach ($name as $key => $subName) {
                $file = self::normalizeFileSpec($subName, $tmpName[$key] ?? null, $type[$key] ?? null, $error[$key] ?? null);

                if ($file !== null) {
                    $files[$key] = $file;
                }
            }

            return $files === [] ? null : $files;
        }

        if ((int) $error === UPLOAD_ERR_NO_FILE || !is_string($name)) {
            return null;
        }

        return new UploadedFile((string) $tmpName, $name, is_string($type) && $type !== '' ? $type : null, (int) $error);
    }

    /** @return array{0: array, 1: array} [données sans fichiers, fichiers] */
    private static function extractFiles(array $data): array
    {
        $files = [];

        foreach ($data as $key => $value) {
            if ($value instanceof UploadedFile) {
                $files[$key] = $value;
                unset($data[$key]);
            } elseif (is_array($value) && $value !== [] && array_filter($value, fn ($item) => !$item instanceof UploadedFile) === []) {
                $files[$key] = $value; // name="photos[]" : une liste de fichiers
                unset($data[$key]);
            }
        }

        return [$data, $files];
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

    /** Le fichier envoyé pour ce champ, ou null (champ absent, laissé vide, ou tableau de fichiers). */
    public function file(string $key): ?UploadedFile
    {
        $file = $this->files[$key] ?? null;

        return $file instanceof UploadedFile ? $file : null;
    }

    /** Un fichier a été envoyé pour ce champ, et il est arrivé sans erreur. */
    public function hasFile(string $key): bool
    {
        return $this->file($key)?->isValid() ?? false;
    }

    /**
     * all() + les fichiers envoyés : ce que valident Controller::validate() et FormRequest. all()
     * seul n'en contient aucun, pour que l'ancienne saisie flashée en session après une erreur de
     * validation (old()) ne contienne jamais d'objet fichier.
     */
    public function allWithFiles(): array
    {
        return array_merge($this->all(), $this->files);
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
