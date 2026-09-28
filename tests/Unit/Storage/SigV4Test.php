<?php

namespace Tests\Unit\Storage;

use Niang\Core\Storage\SigV4;
use PHPUnit\Framework\TestCase;

/**
 * Exemples publiés par AWS dans la documentation d'Amazon S3 (« Examples: Signature Calculations in AWS
 * Signature Version 4 ») : mêmes identifiants, même date, même requête → même signature, au caractère près.
 */
class SigV4Test extends TestCase
{
    private function signer(): SigV4
    {
        return new SigV4('AKIAIOSFODNN7EXAMPLE', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', 'us-east-1');
    }

    private function date(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2013-05-24T00:00:00Z');
    }

    public function test_aws_example_get_object_with_range(): void
    {
        $headers = $this->signer()->sign(
            'GET',
            'https://examplebucket.s3.amazonaws.com/test.txt',
            ['Range' => 'bytes=0-9'],
            hash('sha256', ''),
            $this->date()
        );

        $this->assertSame(
            'AWS4-HMAC-SHA256 Credential=AKIAIOSFODNN7EXAMPLE/20130524/us-east-1/s3/aws4_request, '
            . 'SignedHeaders=host;range;x-amz-content-sha256;x-amz-date, '
            . 'Signature=f0e8bdb87c964420e857bd35b5d6ed310bd44f0170aba48dd91039c6036bdb41',
            $headers['authorization']
        );
        $this->assertSame('20130524T000000Z', $headers['x-amz-date']);
    }

    public function test_aws_example_presigned_url(): void
    {
        $url = $this->signer()->presign('GET', 'https://examplebucket.s3.amazonaws.com/test.txt', 86400, $this->date());

        $this->assertSame(
            'https://examplebucket.s3.amazonaws.com/test.txt?X-Amz-Algorithm=AWS4-HMAC-SHA256'
            . '&X-Amz-Credential=AKIAIOSFODNN7EXAMPLE%2F20130524%2Fus-east-1%2Fs3%2Faws4_request'
            . '&X-Amz-Date=20130524T000000Z&X-Amz-Expires=86400&X-Amz-SignedHeaders=host'
            . '&X-Amz-Signature=aeeed9bbccd4d02ee5c0109b86d86835f995330da4c265957d157751f604d404',
            $url
        );
    }

    public function test_aws_example_put_object(): void
    {
        $body = 'Welcome to Amazon S3.';
        $headers = $this->signer()->sign(
            'PUT',
            'https://examplebucket.s3.amazonaws.com/test%24file.text',
            ['Date' => 'Fri, 24 May 2013 00:00:00 GMT', 'x-amz-storage-class' => 'REDUCED_REDUNDANCY'],
            hash('sha256', $body),
            $this->date()
        );

        $this->assertStringEndsWith('Signature=98ad721746da40c64f1a55b78f14c238d841ea1380cd77a1b5971af0ece108bd', $headers['authorization']);
    }
}
