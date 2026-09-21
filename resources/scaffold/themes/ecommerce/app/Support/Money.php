<?php

namespace App\Support;

/** Affichage des montants stockés en centimes d'euro. */
class Money
{
    /** 12990 => « 129,90 € » (espace fine insécable comme séparateur de milliers, espace insécable avant le symbole). */
    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, ',', "\u{202F}") . "\u{00A0}€";
    }
}
