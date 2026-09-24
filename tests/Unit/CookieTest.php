<?php

namespace Tests\Unit;

use Niang\Core\Cookie;
use Niang\Core\Crypt;
use PHPUnit\Framework\TestCase;

/**
 * Cookie::set() appelle setcookie(), dont l'effet n'est visible qu'à la PROCHAINE requête HTTP
 * réelle (jamais dans $_COOKIE du process courant) : encode() est donc exercée par réflexion pour
 * produire la valeur chiffrée à placer directement dans $_COOKIE, exactement ce qu'un navigateur
 * renverrait à la requête suivante.
 */
class CookieTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_COOKIE['test_cookie'], $_COOKIE['autre_cookie']);
        parent::tearDown();
    }

    public function test_an_encrypted_cookie_round_trips(): void
    {
        $_COOKIE['test_cookie'] = $this->encode('test_cookie', 'valeur-secrete');

        $this->assertSame('valeur-secrete', Cookie::get('test_cookie'));
    }

    public function test_the_value_is_not_readable_by_the_browser(): void
    {
        $encoded = $this->encode('test_cookie', 'valeur-secrete');

        $this->assertStringNotContainsString('valeur-secrete', $encoded);
        $this->assertStringNotContainsString('valeur-secrete', (string) base64_decode(strtr($encoded, '-_', '+/')));
    }

    public function test_a_missing_cookie_returns_the_default(): void
    {
        $this->assertSame('repli', Cookie::get('inexistant', 'repli'));
        $this->assertNull(Cookie::get('inexistant'));
    }

    public function test_a_tampered_value_is_rejected(): void
    {
        $encoded = $this->encode('test_cookie', 'valeur-secrete');
        $encoded[10] = $encoded[10] === 'A' ? 'B' : 'A';
        $_COOKIE['test_cookie'] = $encoded;

        $this->assertNull(Cookie::get('test_cookie'));
    }

    public function test_a_cookie_copied_under_another_name_is_rejected(): void
    {
        $_COOKIE['autre_cookie'] = $this->encode('test_cookie', 'valeur-secrete');

        $this->assertNull(Cookie::get('autre_cookie'));
    }

    public function test_an_expired_cookie_is_rejected_even_if_the_browser_sends_it(): void
    {
        $_COOKIE['test_cookie'] = $this->encode('test_cookie', 'valeur-secrete', -1);

        $this->assertNull(Cookie::get('test_cookie'));
    }

    public function test_a_raw_encrypted_value_without_the_cookie_envelope_is_rejected(): void
    {
        // Chiffré avec le bon contexte mais sans l'enveloppe {v, e} : pas de date d'expiration à vérifier.
        $_COOKIE['test_cookie'] = Crypt::encrypt('valeur-secrete', 'cookie:test_cookie');

        $this->assertNull(Cookie::get('test_cookie'));
    }

    public function test_a_cookie_signed_by_the_previous_format_is_rejected(): void
    {
        $value = 'valeur-secrete';
        $_COOKIE['test_cookie'] = hash_hmac('sha256', $value, (string) getenv('APP_KEY')) . '.' . base64_encode($value);

        $this->assertNull(Cookie::get('test_cookie'));
    }

    public function test_malformed_and_empty_values_are_rejected(): void
    {
        foreach (['pas-de-point-de-separation', '', '....'] as $value) {
            $_COOKIE['test_cookie'] = $value;
            $this->assertNull(Cookie::get('test_cookie'));
        }

        $_COOKIE['test_cookie'] = ['tableau'];
        $this->assertNull(Cookie::get('test_cookie'));
    }

    private function encode(string $name, string $value, int $minutes = 60): string
    {
        $method = new \ReflectionMethod(Cookie::class, 'encode');
        $method->setAccessible(true);

        return $method->invoke(null, $name, $value, $minutes);
    }
}
