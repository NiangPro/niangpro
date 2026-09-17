<?php

namespace Niang\Core;

/** Retourné par Mail::to() — juste pour permettre Mail::to($email)->send($mailable). */
class PendingMail
{
    public function __construct(private string $address)
    {
    }

    public function send(Mailable $mailable): void
    {
        Mail::dispatch($this->address, $mailable);
    }
}
