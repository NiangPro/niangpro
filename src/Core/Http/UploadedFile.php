<?php

namespace Niang\Core\Http;

use Niang\Core\Lang;
use Niang\Core\Storage;

/**
 * Un fichier envoyé par un formulaire (multipart/form-data), obtenu via $request->file('avatar').
 *
 * Tout ce qui vient du navigateur est traité comme non fiable : le nom et le type MIME déclarés par
 * le client (clientName(), clientMimeType()) ne servent qu'à l'affichage. Le type réel est lu dans
 * le contenu du fichier (finfo), l'extension en est déduite, et store() génère toujours un nom
 * aléatoire — jamais le nom d'origine, qui pourrait être « facture.php » ou « ../../.env ».
 * Les fichiers sont rangés dans storage/app/ (via Storage), hors de public/ : ils ne sont jamais
 * exécutables par le serveur web.
 */
final class UploadedFile
{
    /** Types MIME réels reconnus => extension utilisée pour le fichier stocké. */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/svg+xml' => 'svg',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/json' => 'json',
        'application/zip' => 'zip',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'audio/mpeg' => 'mp3',
        'video/mp4' => 'mp4',
    ];

    private bool $moved = false;

    /**
     * @param bool $test true pour un fichier fabriqué par un test (fake()) : il n'est pas passé par
     *                   un vrai upload HTTP, donc is_uploaded_file()/move_uploaded_file() le refuseraient.
     */
    public function __construct(
        private string $path,
        private string $clientName,
        private ?string $clientMimeType = null,
        private int $error = UPLOAD_ERR_OK,
        private bool $test = false,
    ) {
    }

    /**
     * Fichier de test, pour simuler un upload dans un TestCase :
     * $this->post('/avatar', ['avatar' => UploadedFile::fake('photo.png', $contenu)]).
     */
    public static function fake(string $clientName, string $contents = '', ?string $clientMimeType = null): static
    {
        $path = tempnam(sys_get_temp_dir(), 'niang-upload-');
        file_put_contents($path, $contents);

        return new static($path, $clientName, $clientMimeType, UPLOAD_ERR_OK, true);
    }

    /** Une vraie image PNG de la taille demandée (générée sans GD), pour tester les règles image/dimensions. */
    public static function fakeImage(string $clientName = 'image.png', int $width = 10, int $height = 10): static
    {
        return static::fake($clientName, self::pngBytes($width, $height), 'image/png');
    }

    /** Nom d'origine envoyé par le navigateur — à n'utiliser que pour l'affichage. */
    public function clientName(): string
    {
        return $this->clientName;
    }

    /** Type MIME déclaré par le navigateur — non fiable, voir mimeType(). */
    public function clientMimeType(): ?string
    {
        return $this->clientMimeType;
    }

    /** Type MIME réel, lu dans le contenu du fichier. */
    public function mimeType(): string
    {
        if (!is_file($this->path) || !function_exists('finfo_open')) {
            return 'application/octet-stream';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo !== false ? finfo_file($finfo, $this->path) : false;

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }

    /** Extension déduite du type MIME réel (jamais du nom d'origine), ou null si le type n'est pas reconnu. */
    public function extension(): ?string
    {
        return self::EXTENSIONS[$this->mimeType()] ?? null;
    }

    /** Taille en octets. */
    public function size(): int
    {
        $size = is_file($this->path) ? filesize($this->path) : false;

        return $size === false ? 0 : $size;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function error(): int
    {
        return $this->error;
    }

    /** Upload arrivé entier, sans erreur PHP, et réellement issu d'une requête HTTP (ou d'un test). */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK
            && !$this->moved
            && ($this->test || is_uploaded_file($this->path));
    }

    /** Raison lisible d'un upload invalide, reprise telle quelle par le Validator. */
    public function errorMessage(): string
    {
        $key = match ($this->error) {
            UPLOAD_ERR_OK => $this->moved ? 'moved' : 'not_uploaded',
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'too_large',
            UPLOAD_ERR_PARTIAL => 'partial',
            UPLOAD_ERR_NO_FILE => 'no_file',
            UPLOAD_ERR_NO_TMP_DIR => 'no_tmp_dir',
            UPLOAD_ERR_CANT_WRITE => 'cant_write',
            UPLOAD_ERR_EXTENSION => 'extension',
            default => 'unknown',
        };

        return Lang::get("upload.$key", ['max' => (string) ini_get('upload_max_filesize')]);
    }

    /**
     * Range le fichier dans storage/app/<dossier>/ sous un nom aléatoire + l'extension réelle, et
     * retourne son chemin relatif (à enregistrer en base, à relire avec Storage::get()).
     */
    public function store(string $directory = ''): string
    {
        $extension = $this->extension();
        $name = bin2hex(random_bytes(20)) . ($extension !== null ? '.' . $extension : '');

        return $this->storeAs($directory, $name);
    }

    /** Comme store(), avec un nom choisi par l'application — jamais le nom d'origine du client sans le filtrer. */
    public function storeAs(string $directory, string $name): string
    {
        if ($name === '' || str_contains($name, '/') || str_contains($name, '\\') || $name === '.' || $name === '..') {
            throw new \InvalidArgumentException("Nom de fichier invalide : « $name ».");
        }

        if (!$this->isValid()) {
            throw new \RuntimeException('Impossible de stocker ce fichier : ' . $this->errorMessage());
        }

        $relative = ltrim(trim($directory, '/') . '/' . $name, '/');
        $target = Storage::path($relative);

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        $moved = $this->test ? rename($this->path, $target) : move_uploaded_file($this->path, $target);

        if (!$moved) {
            throw new \RuntimeException("Impossible de déplacer le fichier envoyé vers storage/app/$relative.");
        }

        // Jamais exécutable, quelle que soit l'umask du serveur.
        chmod($target, 0644);
        $this->moved = true;
        $this->path = $target;

        return $relative;
    }

    /** PNG minimal (RGB, blanc) construit à la main : signature, IHDR, IDAT compressé, IEND. */
    private static function pngBytes(int $width, int $height): string
    {
        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));

        $rows = str_repeat("\x00" . str_repeat("\xFF\xFF\xFF", $width), $height);

        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            . $chunk('IDAT', (string) gzcompress($rows))
            . $chunk('IEND', '');
    }
}
