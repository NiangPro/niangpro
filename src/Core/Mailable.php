<?php

namespace Niang\Core;

abstract class Mailable
{
    abstract public function subject(): string;

    /** Version texte, toujours envoyée (lue par les clients mail qui n'affichent pas le HTML). */
    abstract public function body(): string;

    /** Version HTML facultative : si elle est fournie, l'email part en texte + HTML (multipart/alternative). */
    public function html(): ?string
    {
        return null;
    }
}
