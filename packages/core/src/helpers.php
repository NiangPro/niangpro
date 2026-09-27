<?php

// Fonctions globales de base : chemins, configuration, langues, échappement. Les helpers des vues et
// des requêtes (route(), view(), csrf_field()...) sont dans le paquet http.

use Niang\Core\Env;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Lang;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        static $base;

        // La racine du projet, telle que Composer la connaît (vendor/composer/installed.php, chemin
        // relatif : juste aussi dans une copie du projet). Repli sans Composer 2 : le premier dossier
        // parent qui a un composer.json et un vendor/, hors de tout vendor/ (le framework installé comme
        // dépendance peut lui-même en contenir un).
        if ($base === null) {
            $root = class_exists(\Composer\InstalledVersions::class) ? \Composer\InstalledVersions::getRootPackage()['install_path'] : null;
            $base = is_string($root) && is_dir($root) ? (string) realpath($root) : dirname(__DIR__, 3);

            if (!is_string($root) || !is_dir($root)) {
                for ($dir = __DIR__; dirname($dir) !== $dir; $dir = dirname($dir)) {
                    if (!str_contains($dir, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) && is_file("$dir/composer.json") && is_dir("$dir/vendor")) {
                        $base = $dir;
                        break;
                    }
                }
            }
        }

        return $path ? $base . '/' . ltrim($path, '/') : $base;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return \Niang\Core\Config::get($key, $default);
    }
}

if (!function_exists('trigger_deprecation')) {
    /**
     * Signale l'usage d'une API dépréciée (roadmap §70) : dépréciée en version N, supprimée en N+1.
     * Même signature et même effet que la fonction de symfony/deprecation-contracts (qui la remplace
     * si elle est installée) : un E_USER_DEPRECATED silencieux, consigné dans les logs par
     * Application::run() et affiché par PHPUnit.
     *
     *   trigger_deprecation('niangpro/framework', '1.6', 'Foo::bar() est déprécié, utilisez Foo::baz().');
     */
    function trigger_deprecation(string $package, string $version, string $message, mixed ...$args): void
    {
        @trigger_error(($package || $version ? "Since $package $version: " : '') . ($args ? vsprintf($message, $args) : $message), \E_USER_DEPRECATED);
    }
}

if (!function_exists('url')) {
    /**
     * URL absolue, construite à partir d'APP_URL — jamais de l'en-tête Host de la requête, qu'un
     * attaquant choisit librement : un lien envoyé par email pointerait sinon vers son site.
     * Indispensable dans un email, où un chemin relatif (« /reset-password/... ») ne mène nulle part.
     */
    function url(string $path = ''): string
    {
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        $base = rtrim((string) Env::get('APP_URL', ''), '/');

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    /** Échappement HTML explicite : <?= e($valeur) ?> plutôt que htmlspecialchars() partout. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('__')) {
    /**
     * Traduction dans la langue courante (voir Niang\Core\Lang) : __('validation.required',
     * ['attribute' => 'email']). Retourne la clé elle-même si elle n'existe dans aucune langue.
     */
    function __(string $key, array $replace = []): string
    {
        return Lang::get($key, $replace);
    }
}

if (!function_exists('trans_choice')) {
    /** Traduction au pluriel : trans_choice('panier.articles', 3) — voir Lang::choice(). */
    function trans_choice(string $key, int|float $count, array $replace = []): string
    {
        return Lang::choice($key, $count, $replace);
    }
}

if (!function_exists('uuid')) {
    /** UUID version 4 aléatoire (RFC 9562), ex. pour une colonne $table->uuid(). */
    function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}

if (!function_exists('abort')) {
    /** abort(404), abort(403, 'Message personnalisé')... — intercepté par Niang\Core\Exceptions\Handler. */
    function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }
}

if (!function_exists('json_for_html')) {
    /**
     * Encode $data en JSON puis échappe pour un usage sûr en attribut HTML — sensible en sécurité
     * (XSS), deux couches nécessaires et complémentaires :
     *  1. json_encode(..., JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) protège
     *     le CONTENU JSON lui-même : JSON_HEX_TAG empêche un `</script>` dans les données de
     *     refermer une balise <script> environnante, JSON_HEX_QUOT/JSON_HEX_APOS/JSON_HEX_AMP
     *     neutralisent les guillemets et esperluettes qui apparaissent DANS les valeurs.
     *  2. htmlspecialchars() sur le résultat protège le CONTENEUR HTML : les guillemets
     *     STRUCTURELS du JSON lui-même (`{"clé":"valeur"}`) restent des caractères `"` littéraux
     *     après l'étape 1 — seuls ceux venant des données sont neutralisés — et cassent un
     *     attribut délimité par des guillemets dès que $data est un tableau/objet (vérifié avec
     *     un vrai navigateur : sans cette seconde couche, `data-props="{"a":"b"}"` se referme au
     *     premier `"` après `{`, et tout le reste devient des attributs HTML arbitraires). Le
     *     navigateur décode les entités HTML d'un attribut à la lecture (dataset, getAttribute) :
     *     JSON.parse() reçoit donc le JSON d'origine, intact.
     *
     * Usage type, pour Alpine.js ou pour hydrater un composant Vue/React monté côté client :
     *
     *   <div data-props="<?= json_for_html($props) ?>" x-data="JSON.parse($el.dataset.props)">
     */
    function json_for_html(mixed $data): string
    {
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        if ($json === false) {
            throw new \JsonException('json_for_html() : données non encodables en JSON — ' . json_last_error_msg());
        }

        return htmlspecialchars($json, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        echo '<pre style="background:#111;color:#0f0;padding:1rem;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        exit(1);
    }
}
