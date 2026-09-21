<?php

namespace App\Support;

/**
 * Mise en forme des articles : date en français, temps de lecture et corps du texte.
 *
 * Le corps d'un article est du texte simple, pas du HTML : des paragraphes séparés par une ligne
 * vide, « ## » pour un intertitre, « > » pour une citation, « - » pour une liste. Tout est échappé,
 * donc aucun risque d'injecter du HTML par le contenu — et pas de moteur de Markdown à embarquer.
 */
class PostFormat
{
    private const MONTHS = [
        1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
    ];

    /** « 2026-09-14 10:00:00 » => « 14 septembre 2026 ». Chaîne vide si la date est illisible. */
    public static function date(string $datetime): string
    {
        $timestamp = strtotime($datetime);

        if ($timestamp === false) {
            return '';
        }

        return date('j', $timestamp) . ('1' === date('j', $timestamp) ? 'er' : '') . ' ' . self::MONTHS[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }

    /** Minutes de lecture, à 200 mots par minute, jamais moins d'une. */
    public static function readingMinutes(string $body): int
    {
        $words = preg_split('/\s+/u', trim($body), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) ceil(count($words) / 200));
    }

    /** Texte simple => HTML sûr (voir la description de la classe). */
    public static function html(string $body): string
    {
        $html = '';

        foreach (preg_split('/\R{2,}/u', trim($body), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $block) {
            $block = trim($block);
            $escaped = htmlspecialchars($block, ENT_QUOTES, 'UTF-8');

            if (str_starts_with($block, '## ')) {
                $html .= '<h2>' . htmlspecialchars(substr($block, 3), ENT_QUOTES, 'UTF-8') . "</h2>\n";
            } elseif (str_starts_with($block, '> ')) {
                $html .= '<blockquote><p>' . htmlspecialchars(substr($block, 2), ENT_QUOTES, 'UTF-8') . "</p></blockquote>\n";
            } elseif (str_starts_with($block, '- ')) {
                $items = array_map(
                    static fn (string $line): string => '<li>' . htmlspecialchars(ltrim(substr(trim($line), 1)), ENT_QUOTES, 'UTF-8') . '</li>',
                    preg_split('/\R/u', $block) ?: []
                );
                $html .= '<ul>' . implode('', $items) . "</ul>\n";
            } else {
                $html .= '<p>' . nl2br($escaped) . "</p>\n";
            }
        }

        return $html;
    }
}
