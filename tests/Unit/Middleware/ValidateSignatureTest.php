<?php

namespace Tests\Unit\Middleware;

use App\Middleware\ValidateSignature;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\UrlSignature;
use PHPUnit\Framework\TestCase;

class ValidateSignatureTest extends TestCase
{
    public function test_lets_a_validly_signed_request_through(): void
    {
        $request = $this->requestFor(UrlSignature::sign('/reset-password', 3600));

        $response = (new ValidateSignature())->handle($request, fn (Request $r) => Response::html('ok'));

        $this->assertSame('ok', $response->getContent());
    }

    public function test_rejects_a_request_missing_its_signature(): void
    {
        $request = $this->requestFor('/reset-password');

        $this->expectException(HttpException::class);

        try {
            (new ValidateSignature())->handle($request, fn (Request $r) => Response::html('ok'));
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_rejects_a_request_with_a_tampered_parameter(): void
    {
        $signed = UrlSignature::sign('/reset-password?email=awa@example.test', 3600);
        $request = $this->requestFor($signed);
        $request->query['email'] = 'eve@example.test';

        $this->expectException(HttpException::class);
        (new ValidateSignature())->handle($request, fn (Request $r) => Response::html('ok'));
    }

    public function test_rejects_an_expired_request(): void
    {
        $request = $this->requestFor(UrlSignature::sign('/reset-password', -1));

        $this->expectException(HttpException::class);
        (new ValidateSignature())->handle($request, fn (Request $r) => Response::html('ok'));
    }

    private function requestFor(string $signedUrl): Request
    {
        $questionMark = strpos($signedUrl, '?');
        $path = $questionMark === false ? $signedUrl : substr($signedUrl, 0, $questionMark);
        $query = [];

        if ($questionMark !== false) {
            parse_str(substr($signedUrl, $questionMark + 1), $query);
        }

        return new Request('GET', $path, $query);
    }
}
