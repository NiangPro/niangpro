<?php

namespace Tests\Unit\Http;

use Niang\Core\Http\Psr7Bridge;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/** nyholm/psr7 est en require-dev (jamais en production, voir composer.json) : disponible ici pour tester la vraie conversion. */
class Psr7BridgeTest extends TestCase
{
    public function test_to_psr_request_carries_over_method_uri_query_and_body(): void
    {
        $request = new Request(
            'POST',
            '/contact',
            ['page' => '2'],
            ['name' => 'Awa'],
            ['HTTP_HOST' => 'niangpro.test'],
            ['X-Custom' => 'valeur'],
        );

        $psrRequest = Psr7Bridge::toPsrRequest($request);

        $this->assertSame('POST', $psrRequest->getMethod());
        $this->assertSame('niangpro.test', $psrRequest->getUri()->getHost());
        $this->assertSame('/contact', $psrRequest->getUri()->getPath());
        $this->assertSame('page=2', $psrRequest->getUri()->getQuery());
        $this->assertSame(['page' => '2'], $psrRequest->getQueryParams());
        $this->assertSame(['name' => 'Awa'], $psrRequest->getParsedBody());
        $this->assertSame('valeur', $psrRequest->getHeaderLine('X-Custom'));
    }

    public function test_to_psr_request_defaults_to_http_and_localhost_without_a_host_header(): void
    {
        $request = new Request('GET', '/');

        $psrRequest = Psr7Bridge::toPsrRequest($request);

        $this->assertSame('http', $psrRequest->getUri()->getScheme());
        $this->assertSame('localhost', $psrRequest->getUri()->getHost());
    }

    public function test_to_psr_response_then_from_psr_response_is_a_round_trip(): void
    {
        $response = Response::json(['ok' => true], 201)->header('X-Trace', 'abc');

        $roundTripped = Psr7Bridge::fromPsrResponse(Psr7Bridge::toPsrResponse($response));

        $this->assertSame(201, $roundTripped->getStatus());
        $this->assertSame('{"ok":true}', $roundTripped->getContent());
        $this->assertSame('application/json; charset=utf-8', $roundTripped->getHeader('Content-Type'));
        $this->assertSame('abc', $roundTripped->getHeader('X-Trace'));
    }

    public function test_from_psr_response_needs_no_concrete_implementation_to_be_installed(): void
    {
        // ResponseInterface expose déjà tout ce qu'il faut lire : un stub maison (sans nyholm)
        // suffit, exactement ce que garantit fromPsrResponse() (voir sa docblock).
        $psrResponse = new Psr7BridgeStubResponse(418, ['X-Stub' => ['oui']], 'corps');

        $response = Psr7Bridge::fromPsrResponse($psrResponse);

        $this->assertSame(418, $response->getStatus());
        $this->assertSame('oui', $response->getHeader('X-Stub'));
        $this->assertSame('corps', $response->getContent());
    }

    public function test_a_missing_psr7_implementation_raises_a_clear_error_message(): void
    {
        $method = new \ReflectionMethod(Psr7Bridge::class, 'requireClass');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Installez un-paquet-inexistant pour utiliser Psr7Bridge (composer require un-paquet-inexistant).');

        $method->invoke(null, 'Une\\Classe\\Qui\\Nexiste\\Pas', 'un-paquet-inexistant');
    }
}

/** Stub minimal, sans nyholm/psr7 : preuve que fromPsrResponse() ne dépend d'aucune implémentation concrète. */
final class Psr7BridgeStubResponse implements ResponseInterface
{
    /** @param array<string, string[]> $headers */
    public function __construct(private int $status, private array $headers, private string $body)
    {
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getReasonPhrase(): string
    {
        return '';
    }

    public function withStatus($code, $reasonPhrase = ''): static
    {
        return new self($code, $this->headers, $this->body);
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion($version): static
    {
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader($name): array
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): static
    {
        $headers = $this->headers;
        $headers[$name] = (array) $value;

        return new self($this->status, $headers, $this->body);
    }

    public function withAddedHeader($name, $value): static
    {
        return $this;
    }

    public function withoutHeader($name): static
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return new Psr7BridgeStubStream($this->body);
    }

    public function withBody(StreamInterface $body): static
    {
        return new self($this->status, $this->headers, (string) $body);
    }
}

/** Stub minimal de StreamInterface : seul __toString() est réellement exercé par le pont. */
final class Psr7BridgeStubStream implements StreamInterface
{
    public function __construct(private string $content)
    {
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
    }

    public function rewind(): void
    {
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        return 0;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        return $this->content;
    }

    public function getContents(): string
    {
        return $this->content;
    }

    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }
}
