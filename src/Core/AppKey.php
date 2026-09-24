<?php

namespace Niang\Core;

use Niang\Core\Exceptions\ConfigurationException;

/**
 * APP_KEY : le secret qui signe les URLs (UrlSignature), hache les jetons API (ApiToken) et chiffre
 * les cookies (Crypt). Sans elle, aucune clé de repli : une valeur par défaut connue de tous
 * rendrait ces signatures falsifiables (un lien de réinitialisation de mot de passe valide pour
 * n'importe quel compte). `composer create-project` et `niang new` la génèrent automatiquement ;
 * sinon, `niang key:generate`.
 */
final class AppKey
{
    public static function get(): string
    {
        $key = (string) Env::get('APP_KEY', '');

        if ($key === '') {
            throw new ConfigurationException(
                'APP_KEY est vide : lancez `./bin/niang key:generate` (ou définissez APP_KEY dans l\'environnement).'
            );
        }

        return $key;
    }

    /** Clé dérivée pour un usage précis (HKDF) : chiffrer ne réutilise jamais la clé brute des signatures HMAC. */
    public static function derive(string $purpose): string
    {
        return hash_hkdf('sha256', self::get(), 32, "niangpro:$purpose");
    }

    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /** Écrit une nouvelle APP_KEY dans un fichier .env (remplace la ligne existante, ou l'ajoute). */
    public static function writeTo(string $envPath, ?string $key = null): string
    {
        $key ??= self::generate();
        $env = file_exists($envPath) ? (string) file_get_contents($envPath) : '';

        if (preg_match('/^APP_KEY=.*$/m', $env)) {
            $env = (string) preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$key", $env);
        } else {
            $env .= (($env !== '' && !str_ends_with($env, "\n")) ? "\n" : '') . "APP_KEY=$key\n";
        }

        file_put_contents($envPath, $env);

        return $key;
    }
}
