<?php

declare(strict_types=1);

namespace Niang\Core;

/** Retourné par Container::when() : when(A::class)->needs(Contrat::class)->give(Implementation::class). */
final class ContextualBindingBuilder
{
    private ?string $needs = null;

    public function __construct(private Container $container, private string $consumer)
    {
    }

    public function needs(string $abstract): static
    {
        $this->needs = $abstract;
        return $this;
    }

    /** @param \Closure|class-string $implementation */
    public function give(\Closure|string $implementation): void
    {
        if ($this->needs === null) {
            throw new Exceptions\ContainerException('when()->give() : appelez needs() avant give().');
        }

        $this->container->addContextualBinding($this->consumer, $this->needs, $implementation);
    }
}
