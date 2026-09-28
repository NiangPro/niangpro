<?php

namespace Niang\Core;

/**
 * Chiffrement authentifié AES-256-GCM (extension openssl) avec une clé dérivée d'APP_KEY.
 *
 * GCM chiffre ET authentifie : un seul octet modifié, et decrypt() retourne null — pas besoin d'un
 * HMAC séparé. $context est lié au chiffré sans y être stocké (données associées) : une valeur
 * chiffrée pour le contexte « cookie:panier » ne se déchiffre pas sous « cookie:remember », ce qui
 * empêche de recopier un cookie valide sous un autre nom.
 *
 * Format : base64url( version (1 octet) | IV (12 octets) | tag (16 octets) | texte chiffré ).
 */
final class Crypt
{
    private const CIPHER = 'aes-256-gcm';
    private const VERSION = "\x01";
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    public static function encrypt(string $plaintext, string $context = ''): string
    {
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag, self::aad($context), self::TAG_LENGTH);

        if ($ciphertext === false) {
            throw new \RuntimeException('Chiffrement impossible : ' . (openssl_error_string() ?: 'erreur openssl inconnue'));
        }

        return rtrim(strtr(base64_encode(self::VERSION . $iv . $tag . $ciphertext), '+/', '-_'), '=');
    }

    /** Le texte d'origine, ou null si la valeur est malformée, modifiée, ou chiffrée pour un autre contexte / une autre clé. */
    public static function decrypt(string $payload, string $context = ''): ?string
    {
        $raw = base64_decode(strtr($payload, '-_', '+/'), true);

        if ($raw === false || strlen($raw) < 1 + self::IV_LENGTH + self::TAG_LENGTH || $raw[0] !== self::VERSION) {
            return null;
        }

        $iv = substr($raw, 1, self::IV_LENGTH);
        $tag = substr($raw, 1 + self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, 1 + self::IV_LENGTH + self::TAG_LENGTH);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag, self::aad($context));

        return $plaintext === false ? null : $plaintext;
    }

    private static function key(): string
    {
        return AppKey::derive('encryption');
    }

    private static function aad(string $context): string
    {
        return 'niangpro:' . $context;
    }
}
