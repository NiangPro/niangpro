<?php

namespace Tests\Unit;

use Niang\Core\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    /**
     * RFC 6238, annexe B (SHA-1, 8 chiffres, secret ASCII « 12345678901234567890 »).
     *
     * @dataProvider rfcVectors
     */
    public function test_rfc_6238_test_vectors(int $timestamp, string $expected): void
    {
        $this->assertSame($expected, Totp::code(Totp::base32Encode('12345678901234567890'), $timestamp, 8));
    }

    /** @return array<string, array{0: int, 1: string}> */
    public static function rfcVectors(): array
    {
        return [
            'an 1970' => [59, '94287082'],
            '2005 a' => [1111111109, '07081804'],
            '2005 b' => [1111111111, '14050471'],
            'an 2009' => [1234567890, '89005924'],
            'an 2033' => [2000000000, '69279037'],
            '2603 (au-delà de 32 bits)' => [20000000000, '65353130'],
        ];
    }

    public function test_base32_matches_rfc_4648(): void
    {
        foreach (['f' => 'MY', 'fo' => 'MZXQ', 'foo' => 'MZXW6', 'foob' => 'MZXW6YQ', 'fooba' => 'MZXW6YTB', 'foobar' => 'MZXW6YTBOI'] as $plain => $encoded) {
            $this->assertSame($encoded, Totp::base32Encode($plain));
            $this->assertSame($plain, Totp::base32Decode($encoded));
        }

        $this->assertSame('foobar', Totp::base32Decode('mzxw 6ytb oi=='), 'saisie manuelle tolérée');
    }

    public function test_an_invalid_secret_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Totp::base32Decode('ABC1');
    }

    public function test_generated_secrets_are_160_bits_and_unique(): void
    {
        $secret = Totp::generateSecret();

        $this->assertSame(32, strlen($secret));
        $this->assertSame(20, strlen(Totp::base32Decode($secret)));
        $this->assertNotSame($secret, Totp::generateSecret());
    }

    public function test_verify_tolerates_one_period_of_clock_drift_and_returns_the_step(): void
    {
        $secret = Totp::generateSecret();
        $now = 1_700_000_000;
        $step = intdiv($now, 30);

        $this->assertSame($step, Totp::verify($secret, Totp::code($secret, $now), 1, $now));
        $this->assertSame($step - 1, Totp::verify($secret, Totp::code($secret, $now - 30), 1, $now));
        $this->assertSame($step + 1, Totp::verify($secret, Totp::code($secret, $now + 30), 1, $now));
        $this->assertNull(Totp::verify($secret, Totp::code($secret, $now - 90), 1, $now), 'trop ancien');
    }

    public function test_verify_rejects_malformed_codes_and_accepts_spaces(): void
    {
        $secret = Totp::generateSecret();
        $now = 1_700_000_000;
        $code = Totp::code($secret, $now);

        $this->assertNotNull(Totp::verify($secret, substr($code, 0, 3) . ' ' . substr($code, 3), 1, $now));
        foreach (['', '12345', '1234567', 'abcdef', $code . 'x'] as $bad) {
            $this->assertNull(Totp::verify($secret, $bad, 1, $now));
        }
    }

    public function test_provisioning_uri(): void
    {
        $uri = Totp::provisioningUri('JBSWY3DPEHPK3PXP', 'awa@example.test', 'Ma Boutique');

        $this->assertSame(
            'otpauth://totp/Ma%20Boutique:awa%40example.test?secret=JBSWY3DPEHPK3PXP&issuer=Ma%20Boutique&algorithm=SHA1&digits=6&period=30',
            $uri
        );
    }
}
