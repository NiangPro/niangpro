<?php

namespace Tests\Unit;

use Niang\Core\UrlSignature;
use PHPUnit\Framework\TestCase;

class UrlSignatureTest extends TestCase
{
    public function test_a_freshly_signed_url_validates(): void
    {
        $signed = UrlSignature::sign('/reset-password');

        $this->assertTrue(UrlSignature::validate($signed));
    }

    public function test_a_signed_url_with_named_parameters_validates(): void
    {
        $signed = UrlSignature::sign('/verify-email/42?token=abc');

        $this->assertTrue(UrlSignature::validate($signed));
    }

    public function test_the_query_string_order_does_not_affect_validation(): void
    {
        $signed = UrlSignature::sign('/reset-password?email=awa%40example.test&type=reset');

        $questionMark = strpos($signed, '?');
        $base = substr($signed, 0, $questionMark);
        parse_str(substr($signed, $questionMark + 1), $query);

        $reordered = $base . '?' . http_build_query(array_reverse($query, true));

        $this->assertTrue(UrlSignature::validate($reordered));
    }

    public function test_tampering_with_a_parameter_invalidates_the_url(): void
    {
        $signed = UrlSignature::sign('/reset-password?email=awa@example.test');

        $tampered = str_replace('email=awa%40example.test', 'email=eve%40example.test', $signed);

        $this->assertFalse(UrlSignature::validate($tampered));
    }

    public function test_an_expired_url_does_not_validate(): void
    {
        $signed = UrlSignature::sign('/reset-password', -1);

        $this->assertFalse(UrlSignature::validate($signed));
    }

    public function test_a_url_without_expiration_never_expires(): void
    {
        $signed = UrlSignature::sign('/verify-email/42');

        $this->assertTrue(UrlSignature::validate($signed));
    }

    public function test_a_url_without_a_signature_is_invalid(): void
    {
        $this->assertFalse(UrlSignature::validate('/reset-password?email=awa@example.test'));
    }

    public function test_a_url_with_an_empty_signature_is_invalid(): void
    {
        $this->assertFalse(UrlSignature::validate('/reset-password?signature='));
    }
}
