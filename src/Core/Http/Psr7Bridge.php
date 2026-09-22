<?php

namespace Niang\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Pont pur entre les Request/Response maison de NiangPro et PSR-7 (psr/http-message, interfaces
 * seules en dépendance — voir composer.json). Ne remplace rien : NiangPro garde son
 * Request/Response simples partout, ce pont ne sert qu'à brancher un composant PSR-15 tiers
 * (voir Psr15Adapter) sans réécrire le framework autour d'objets immuables.
 *
 * `fromPsrResponse()` n'a besoin d'aucune implémentation concrète : ResponseInterface expose déjà
 * tout ce qu'il faut lire. `toPsrRequest()` et `toPsrResponse()`, à l'inverse, doivent CONSTRUIRE
 * un objet concret à partir de rien — psr/http-message ne fournissant que des interfaces, une
 * implémentation (nyholm/psr7, suggérée dans composer.json, jamais requise) doit être installée ;
 * son absence lève une exception claire plutôt qu'une erreur PHP opaque sur une classe manquante.
 */
class Psr7Bridge
{
    private const NYHOLM_FACTORY = 'Nyholm\\Psr7\\Factory\\Psr17Factory';

    public static function toPsrRequest(Request $request): ServerRequestInterface
    {
        $factory = self::factory();

        $psrRequest = $factory->createServerRequest($request->method, self::uri($request), $request->server)
            ->withQueryParams($request->query)
            ->withParsedBody($request->body)
            ->withBody($factory->createStream(http_build_query($request->body)));

        foreach ($request->headers as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }

        return $psrRequest;
    }

    public static function toPsrResponse(Response $response): ResponseInterface
    {
        $factory = self::factory();

        $psrResponse = $factory->createResponse($response->getStatus())
            ->withBody($factory->createStream($response->getContent()));

        foreach ($response->getHeaders() as $name => $value) {
            $psrResponse = $psrResponse->withHeader($name, $value);
        }

        return $psrResponse;
    }

    public static function fromPsrResponse(ResponseInterface $psrResponse): Response
    {
        $response = (new Response())
            ->status($psrResponse->getStatusCode())
            ->content((string) $psrResponse->getBody());

        foreach ($psrResponse->getHeaders() as $name => $values) {
            $response->header($name, implode(', ', $values));
        }

        return $response;
    }

    private static function uri(Request $request): string
    {
        $https = $request->server['HTTPS'] ?? '';
        $scheme = ($https !== '' && $https !== 'off') ? 'https' : 'http';
        $host = $request->server['HTTP_HOST'] ?? 'localhost';
        $query = $request->query ? '?' . http_build_query($request->query) : '';

        return "$scheme://$host{$request->uri}$query";
    }

    /** @return \Nyholm\Psr7\Factory\Psr17Factory */
    private static function factory(): object
    {
        self::requireClass(self::NYHOLM_FACTORY, 'nyholm/psr7');

        return new (self::NYHOLM_FACTORY)();
    }

    /**
     * Extraite de factory() pour rester testable sans dépendre de la présence réelle de
     * nyholm/psr7 dans l'environnement de test (voir Psr7BridgeTest) : $class et $package sont
     * des paramètres, jamais codés en dur ici.
     *
     * @internal
     */
    private static function requireClass(string $class, string $package): void
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("Installez $package pour utiliser Psr7Bridge (composer require $package).");
        }
    }
}
