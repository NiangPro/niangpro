<?php

declare(strict_types=1);

namespace Niang\Core\Events;

/** Événement du framework (roadmap §49) : Au début de Application::handle(), avant le mode maintenance et le routeur. */
final class RequestReceived
{
    public function __construct(public readonly \Niang\Core\Http\Request $request)
    {
    }
}
