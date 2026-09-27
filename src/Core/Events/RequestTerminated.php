<?php

namespace Niang\Core\Events;

/** Événement du framework (roadmap §49) : Après l'envoi de la réponse (le visiteur ne l'attend plus si fastcgi_finish_request() est disponible) : nettoyage, statistiques. */
final class RequestTerminated
{
    public function __construct(public readonly \Niang\Core\Http\Request $request, public readonly \Niang\Core\Http\Response $response)
    {
    }
}
