<?php

namespace Niang\Core;

/**
 * Traductions des textes que voit un visiteur : messages de validation, erreurs d'upload, pages
 * d'erreur HTTP, pagination. Un fichier PHP par groupe, par langue : lang/<langue>/<groupe>.php
 * à la racine du projet — un projet NiangPro étant une copie complète du framework, l'application
 * modifie ou complète ces fichiers directement, sans mécanisme de surcharge.
 *
 *   Lang::get('validation.required', ['attribute' => 'email'])  // « Le champ email est requis. »
 *   __('validation.required', ['attribute' => 'email'])         // même chose, en helper
 *
 * La langue vient de config('app.locale') (APP_LOCALE, 'fr' par défaut) ; Lang::setLocale() la
 * change pour la requête en cours (ex. dans un middleware). Une clé absente de la langue courante
 * est cherchée dans la langue de repli (config('app.fallback_locale'), 'fr'), puis retournée telle
 * quelle — un oubli se voit sur la page au lieu de la faire planter.
 */
final class Lang
{
    private static ?string $locale = null;

    /** @var array<string, array<string, mixed>> "<langue>.<groupe>" => lignes */
    private static array $loaded = [];

    public static function locale(): string
    {
        return self::$locale ?? (string) Config::get('app.locale', Env::get('APP_LOCALE', 'fr'));
    }

    public static function setLocale(string $locale): void
    {
        if (preg_match('/^[a-z]{2,3}([_-][A-Za-z]{2,4})?$/', $locale) !== 1) {
            throw new \InvalidArgumentException("Code de langue invalide : « $locale ».");
        }

        self::$locale = $locale;
    }

    public static function fallbackLocale(): string
    {
        return (string) Config::get('app.fallback_locale', 'fr');
    }

    /** La traduction existe-t-elle (dans la langue demandée, sans repli) ? */
    public static function has(string $key, ?string $locale = null): bool
    {
        return self::lookup($key, $locale ?? self::locale()) !== null;
    }

    /**
     * @param array<string, string|int|float> $replace remplace « :nom » dans le texte ; « :Nom » met
     *                                                 la première lettre en majuscule
     */
    public static function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale ??= self::locale();
        $line = self::lookup($key, $locale) ?? self::lookup($key, self::fallbackLocale());

        if (!is_string($line)) {
            return $key;
        }

        // Les noms les plus longs d'abord : « :min » ne doit pas mordre sur « :minutes ».
        uksort($replace, fn ($a, $b) => strlen((string) $b) <=> strlen((string) $a));

        foreach ($replace as $name => $value) {
            $value = (string) $value;
            $line = str_replace(
                [':' . ucfirst((string) $name), ':' . $name],
                [mb_strtoupper(mb_substr($value, 0, 1)) . mb_substr($value, 1), $value],
                $line
            );
        }

        return $line;
    }

    /**
     * Une section entière (ex. 'validation.attributes'), langue courante puis langue de repli —
     * pour des clés qui contiennent elles-mêmes des points ('items.*.name', 'address.city').
     *
     * @return array<string, mixed>
     */
    public static function section(string $key): array
    {
        $lines = self::lookup($key, self::locale());

        if (!is_array($lines)) {
            $lines = self::lookup($key, self::fallbackLocale());
        }

        return is_array($lines) ? $lines : [];
    }

    /** @internal remet la langue par défaut et vide le cache — pour les tests. */
    public static function reset(): void
    {
        self::$locale = null;
        self::$loaded = [];
    }

    private static function lookup(string $key, string $locale): mixed
    {
        [$group, $item] = array_pad(explode('.', $key, 2), 2, null);

        if ($item === null || preg_match('/^[a-z0-9_-]+$/', $group) !== 1) {
            return null;
        }

        $lines = self::$loaded["$locale.$group"] ??= self::load($locale, $group);
        $value = $lines;

        foreach (explode('.', $item) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private static function load(string $locale, string $group): array
    {
        $file = base_path("lang/$locale/$group.php");

        if (!is_file($file)) {
            return [];
        }

        $lines = require $file;

        return is_array($lines) ? $lines : [];
    }
}
