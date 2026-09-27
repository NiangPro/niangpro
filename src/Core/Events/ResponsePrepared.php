<?php

namespace Niang\Core\Events;

/** Événement du framework (roadmap §49) : Réponse prête, en-têtes de sécurité compris, juste avant l'envoi : un écouteur peut encore la modifier ($event->response->header(...)). */
final class ResponsePrepared
{
    public function __construct(public readonly \Niang\Core\Http\Request $request, public readonly \Niang\Core\Http\Response $response)
    {
    }
}
