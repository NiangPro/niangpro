<?php

namespace Tests\Unit;

use Niang\Core\Cookie;
use PHPUnit\Framework\TestCase;

/**
 * Aucun test n'existait sur cette classe avant ce fichier, alors qu'elle signe des cookies
 * (HMAC-SHA256 + hash_equals) — Cookie::set() appelle setcookie(), dont l'effet n'est visible
 * que sur la PROCHAINE requête HTTP réelle (jamais dans $_COOKIE du process courant) : sign()
 * est donc exercée par réflexion pour produire une valeur signée à placer directement dans
 * $_COOKIE, exactement ce qu'un navigateur renverrait à la requête suivante.
 */
class CookieTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_COOKIE['test_cookie']);
        parent::tearDown();
    }

    public function test_a_validly_signed_cookie_round_trips(): void
    {
        $_COOKIE['test_cookie'] = $this->sign('valeur-secrete');

        $this->assertSame('valeur-secrete', Cookie::get('test_cookie'));
    }

    public function test_a_missing_cookie_returns_the_default(): void
    {
        $this->assertSame('repli', Cookie::get('inexistant', 'repli'));
        $this->assertNull(Cookie::get('inexistant'));
    }

    public function test_a_tampered_value_is_rejected(): void
    {
        $signed = $this->sign('valeur-secrete');
        [$signature, $encoded] = explode('.', $signed, 2);

        // Change la valeur encodée sans retoucher la signature : hash_equals doit échouer.
        $_COOKIE['test_cookie'] = $signature . '.' . base64_encode('valeur-modifiee');

        $this->assertNull(Cookie::get('test_cookie'));
    }

    public function test_a_tampered_signature_is_rejected(): void
    {
        $signed = $this->sign('valeur-secrete');
        [$signature, $encoded] = explode('.', $signed, 2);

        $_COOKIE['test_cookie'] = strrev($signature) . '.' . $encoded;

        $this->assertNull(Cookie::get('test_cookie'));
    }

    public function test_a_malformed_value_without_a_separator_is_rejected(): void
    {
        $_COOKIE['test_cookie'] = 'pas-de-point-de-separation';

        $this->assertNull(Cookie::get('test_cookie'));
    }

    public function test_an_empty_value_is_rejected(): void
    {
        $_COOKIE['test_cookie'] = '';

        $this->assertNull(Cookie::get('test_cookie'));
    }

    private function sign(string $value): string
    {
        $method = new \ReflectionMethod(Cookie::class, 'sign');
        $method->setAccessible(true);

        return $method->invoke(null, $value);
    }
}
