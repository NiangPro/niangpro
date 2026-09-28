<?php

namespace Tests\Unit;

use Niang\Core\AppKey;
use Niang\Core\Crypt;
use Niang\Core\Exceptions\ConfigurationException;
use PHPUnit\Framework\TestCase;

class CryptTest extends TestCase
{
    /** @param \Closure(): mixed $callback */
    private function withAppKey(?string $key, \Closure $callback): mixed
    {
        $previous = getenv('APP_KEY');
        $key === null ? putenv('APP_KEY') : putenv("APP_KEY=$key");

        try {
            return $callback();
        } finally {
            $previous === false ? putenv('APP_KEY') : putenv("APP_KEY=$previous");
        }
    }

    public function test_a_value_round_trips_and_is_not_readable(): void
    {
        $encrypted = Crypt::encrypt('panier: 3 articles, total 45 000 FCFA');

        $this->assertStringNotContainsString('panier', $encrypted);
        $this->assertStringNotContainsString('panier', (string) base64_decode(strtr($encrypted, '-_', '+/')));
        $this->assertSame('panier: 3 articles, total 45 000 FCFA', Crypt::decrypt($encrypted));
    }

    public function test_the_same_value_never_encrypts_to_the_same_output(): void
    {
        $this->assertNotSame(Crypt::encrypt('identique'), Crypt::encrypt('identique'));
    }

    public function test_the_output_is_safe_in_cookies_and_urls(): void
    {
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', Crypt::encrypt(random_bytes(100)));
    }

    public function test_any_modified_byte_is_rejected(): void
    {
        $raw = base64_decode(strtr(Crypt::encrypt('valeur'), '-_', '+/'));

        for ($i = 0; $i < strlen($raw); $i++) {
            $tampered = $raw;
            $tampered[$i] = chr(ord($tampered[$i]) ^ 0x01);

            $this->assertNull(Crypt::decrypt(rtrim(strtr(base64_encode($tampered), '+/', '-_'), '=')), "octet $i modifié accepté");
        }
    }

    public function test_a_value_encrypted_for_one_context_is_rejected_in_another(): void
    {
        $encrypted = Crypt::encrypt('42', 'cookie:panier');

        $this->assertSame('42', Crypt::decrypt($encrypted, 'cookie:panier'));
        $this->assertNull(Crypt::decrypt($encrypted, 'cookie:remember'));
        $this->assertNull(Crypt::decrypt($encrypted));
    }

    public function test_a_value_encrypted_with_another_app_key_is_rejected(): void
    {
        $encrypted = $this->withAppKey(str_repeat('a', 64), fn () => Crypt::encrypt('secret'));

        $this->assertNull($this->withAppKey(str_repeat('b', 64), fn () => Crypt::decrypt($encrypted)));
    }

    public function test_malformed_payloads_are_rejected(): void
    {
        foreach (['', 'pas du base64 !', 'AAAA', base64_encode(str_repeat("\x02", 40))] as $payload) {
            $this->assertNull(Crypt::decrypt($payload));
        }
    }

    public function test_it_refuses_to_work_without_an_app_key(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('key:generate');

        $this->withAppKey(null, fn () => Crypt::encrypt('x'));
    }

    public function test_app_key_write_to_replaces_or_appends_the_key(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'niang-env-');

        try {
            file_put_contents($path, "APP_NAME=Demo\nAPP_KEY=\nAPP_DEBUG=true\n");
            $key = AppKey::writeTo($path);
            $this->assertSame("APP_NAME=Demo\nAPP_KEY=$key\nAPP_DEBUG=true\n", file_get_contents($path));

            file_put_contents($path, 'APP_NAME=Demo');
            $key = AppKey::writeTo($path);
            $this->assertSame("APP_NAME=Demo\nAPP_KEY=$key\n", file_get_contents($path));
            $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $key);
        } finally {
            unlink($path);
        }
    }
}
