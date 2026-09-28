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

    /**
     * Intertitres du corps, dans l'ordre, avec l'identifiant posé par html() : de quoi construire le
     * sommaire d'un article.
     *
     * @return list<array{id: string, text: string}>
     */
    public static function headings(string $body): array
    {
        $headings = [];
        $used = [];

        foreach (self::blocks($body) as $block) {
            if (str_starts_with($block, '## ')) {
                $text = trim(substr($block, 3));
                $headings[] = ['id' => self::anchor($text, $used), 'text' => $text];
            }
        }

        return $headings;
    }

    /** Texte simple => HTML sûr (voir la description de la classe). */
    public static function html(string $body): string
    {
        $html = '';
        $used = [];

        foreach (self::blocks($body) as $block) {
            $escaped = htmlspecialchars($block, ENT_QUOTES, 'UTF-8');

            if (str_starts_with($block, '## ')) {
                $text = trim(substr($block, 3));
                $html .= '<h2 id="' . self::anchor($text, $used) . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "</h2>\n";
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

    /** @return list<string> blocs du corps, séparés par une ligne vide */
    private static function blocks(string $body): array
    {
        return array_map('trim', preg_split('/\R{2,}/u', trim($body), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * « Naviguer au clavier » => « section-naviguer-au-clavier » : identifiant stable et lisible pour les
     * liens du sommaire, suffixé (-2, -3...) si deux intertitres se ressemblent. Le préfixe évite toute
     * collision avec un identifiant du reste de la page.
     *
     * @param array<string, true> $used
     */
    private static function anchor(string $text, array &$used): string
    {
        $slug = strtr(mb_strtolower($text), [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'œ' => 'oe', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ñ' => 'n',
        ]);
        $slug = 'section-' . (trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-') ?: 'partie');
        $unique = $slug;

        for ($n = 2; isset($used[$unique]); $n++) {
            $unique = "$slug-$n";
        }

        $used[$unique] = true;

        return $unique;
    }
}
