<?php

namespace App\Support;

/** Formats d'affichage de l'administration, en français, sans dépendre de l'extension intl. */
class AdminFormat
{
    private const MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    private const SHORT_MONTHS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    private const DAYS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

    /** « mercredi 23 septembre 2026 » */
    public static function longDate(string $date): string
    {
        $time = strtotime($date) ?: time();

        return self::DAYS[(int) date('w', $time)] . ' ' . date('j', $time) . ' ' . self::MONTHS[(int) date('n', $time) - 1] . ' ' . date('Y', $time);
    }

    /** « 23 sept. 2026 » */
    public static function date(?string $date): string
    {
        if (!$date || !($time = strtotime($date))) {
            return '—';
        }

        return date('j', $time) . ' ' . self::shortMonth((int) date('n', $time)) . ' ' . date('Y', $time);
    }

    /** « 23 sept. » */
    public static function dayMonth(string $date): string
    {
        $time = strtotime($date) ?: time();

        return date('j', $time) . ' ' . self::shortMonth((int) date('n', $time));
    }

    /** « sept. 2026 » à partir de « 2026-09 » */
    public static function month(string $yearMonth): string
    {
        $time = strtotime($yearMonth . '-01') ?: time();

        return self::shortMonth((int) date('n', $time)) . ' ' . date('Y', $time);
    }

    /** « il y a 3 h », « il y a 2 j », puis la date au-delà d'une semaine. */
    public static function ago(?string $date): string
    {
        if (!$date || !($time = strtotime($date))) {
            return '—';
        }

        $seconds = time() - $time;

        return match (true) {
            $seconds < 60 => "à l'instant",
            $seconds < 3600 => 'il y a ' . intdiv($seconds, 60) . ' min',
            $seconds < 86400 => 'il y a ' . intdiv($seconds, 3600) . ' h',
            $seconds < 7 * 86400 => 'il y a ' . intdiv($seconds, 86400) . ' j',
            default => self::date($date),
        };
    }

    /** « Théière en fonte émaillée » => « theiere-en-fonte-emaillee », pour une URL lisible. */
    public static function slug(string $text): string
    {
        $text = strtr(mb_strtolower(trim($text)), [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'ae',
            'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i',
            'ñ' => 'n', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'œ' => 'oe', 'ø' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ý' => 'y', 'ß' => 'ss',
        ]);

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $text), '-');
    }

    /** 12345 => « 12 345 » */
    public static function number(int|float $value): string
    {
        return number_format($value, 0, ',', "\u{202F}");
    }

    private static function shortMonth(int $month): string
    {
        return self::SHORT_MONTHS[$month - 1];
    }
}
