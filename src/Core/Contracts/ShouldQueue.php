<?php

namespace Niang\Core\Contracts;

/**
 * Marqueur, sans méthode requise : un listener enregistré via Event::listen() qui implémente
 * cette interface est différé sur Queue (voir Niang\Core\Jobs\CallQueuedListener) plutôt
 * qu'exécuté en synchrone au moment de Event::dispatch(). Ne s'applique qu'aux listeners
 * enregistrés comme classe (class-string) — un listener enregistré comme closure reste
 * toujours synchrone, une closure sérialisée ne survivrait pas au passage par la file.
 */
interface ShouldQueue
{
}
