<?php

namespace Niang\Core\Http;

use Niang\Core\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Fait passer un middleware PSR-15 tiers (psr/http-server-middleware, interfaces seules en
 * dépendance) pour un middleware natif NiangPro : implémente le contrat Middleware existant,
 * s'attache donc à une route exactement comme App\Middleware\VerifyCsrfToken ou n'importe quel
 * autre — sans binding dans le Container, il ne saura pas construire le MiddlewareInterface qu'il
 * enrobe, comme pour toute classe dont une dépendance est une interface :
 *
 *   $container->bind(Psr15Adapter::class, fn () => new Psr15Adapter(new UnMiddlewarePsr15()));
 *   // puis, dans routes/web.php : $router->get('/x', [...], [Psr15Adapter::class]);
 *
 * (le binding se fait typiquement dans ServiceProvider::register(), où $this->app->container
 * est accessible.)
 *
 * Limite assumée : si le middleware PSR-15 modifie la requête (withAttribute(), withQueryParams()
 * après coup...), ces changements ne sont PAS répercutés vers la suite du pipeline NiangPro — le
 * Request maison de NiangPro n'a pas de notion d'attributs PSR-7, lui en ajouter reviendrait à
 * commencer la refonte que ce pont évite justement. Un middleware PSR-15 qui COURT-CIRCUITE (ne
 * fait jamais handle()) ou qui MODIFIE LA RÉPONSE fonctionne pleinement ; un middleware qui
 * n'agit que sur des attributs de requête PSR-7 pour un usage plus en aval ne peut pas passer ces
 * attributs à NiangPro par ce pont.
 */
class Psr15Adapter implements Middleware
{
    public function __construct(private MiddlewareInterface $middleware)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        $psrRequest = Psr7Bridge::toPsrRequest($request);

        $handler = new class ($next, $request) implements RequestHandlerInterface {
            public function __construct(private \Closure $next, private Request $request)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return Psr7Bridge::toPsrResponse(($this->next)($this->request));
            }
        };

        return Psr7Bridge::fromPsrResponse($this->middleware->process($psrRequest, $handler));
    }
}
