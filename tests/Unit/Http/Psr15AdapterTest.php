<?php

namespace Tests\Unit\Http;

use Niang\Core\Http\Psr15Adapter;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Psr15AdapterTest extends TestCase
{
    public function test_a_psr15_middleware_that_adds_a_header_actually_changes_the_response(): void
    {
        $adapter = new Psr15Adapter(new Psr15AdapterTestHeaderMiddleware());

        $response = $adapter->handle(new Request('GET', '/'), function (Request $request): Response {
            return Response::html('bonjour');
        });

        $this->assertSame('bonjour', $response->getContent());
        $this->assertSame('depuis-psr15', $response->getHeader('X-Psr15'));
    }

    public function test_a_psr15_middleware_can_short_circuit_without_calling_the_niangpro_pipeline(): void
    {
        $adapter = new Psr15Adapter(new Psr15AdapterTestShortCircuitMiddleware());
        $called = false;

        $response = $adapter->handle(new Request('GET', '/'), function (Request $request) use (&$called): Response {
            $called = true;

            return Response::html('jamais atteint');
        });

        $this->assertFalse($called);
        $this->assertSame(403, $response->getStatus());
    }
}

/** Middleware PSR-15 minimal écrit pour ce test : laisse passer, puis ajoute un header à la réponse. */
class Psr15AdapterTestHeaderMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)->withHeader('X-Psr15', 'depuis-psr15');
    }
}

/** Middleware PSR-15 minimal qui court-circuite : ne délègue jamais au handler NiangPro. */
class Psr15AdapterTestShortCircuitMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return (new \Nyholm\Psr7\Factory\Psr17Factory())->createResponse(403);
    }
}
