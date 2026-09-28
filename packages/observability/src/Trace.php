<?php

declare(strict_types=1);

namespace Niang\Core;

use Niang\Core\Http\Request;

/**
 * Corrélation d'une requête (roadmap §53) : un identifiant de requête, renvoyé dans l'en-tête
 * X-Request-Id et ajouté à chaque ligne de log, et le contexte de trace W3C (traceparent), repris
 * de la requête entrante et propagé aux appels sortants de Http\Client. Un proxy, un APM ou
 * OpenTelemetry en amont relient ainsi leurs traces aux logs de l'application, sans SDK.
 *
 * @experimental le contenu de context() peut encore évoluer (voir docs/API_STABILITY.md).
 */
final class Trace
{
    private static ?string $traceId = null;
    private static ?string $spanId = null;
    private static string $flags = '00';
    private static ?string $requestId = null;
    private static bool $active = false;

    /** Appelé par Application::handle() au début de chaque requête. */
    public static function begin(Request $request): void
    {
        self::reset();
        self::$active = true;

        $parent = strtolower(trim((string) $request->header('traceparent', '')));

        // version-traceid-parentid-flags ; un identifiant tout à zéro est invalide (W3C Trace Context §3.2).
        if (preg_match('/^([0-9a-f]{2})-([0-9a-f]{32})-([0-9a-f]{16})-([0-9a-f]{2})$/', $parent, $m) === 1
            && $m[1] !== 'ff' && $m[2] !== str_repeat('0', 32) && $m[3] !== str_repeat('0', 16)) {
            self::$traceId = $m[2];
            self::$flags = $m[4];
        }

        // Fourni par un répartiteur de charge ou un proxy : repris tel quel s'il est raisonnable.
        $incoming = trim((string) $request->header('X-Request-Id', ''));

        if (preg_match('/^[A-Za-z0-9._:\-]{8,128}$/', $incoming) === 1) {
            self::$requestId = $incoming;
        }
    }

    /** Vrai pendant le traitement d'une requête HTTP : les logs portent alors request_id. */
    public static function active(): bool
    {
        return self::$active;
    }

    /** Hors requête (CLI, worker), un identifiant est créé au premier appel et reste le même. */
    public static function traceId(): string
    {
        return self::$traceId ??= bin2hex(random_bytes(16));
    }

    /** Identifiant de l'opération courante (cette requête), parent des appels sortants. */
    public static function spanId(): string
    {
        return self::$spanId ??= bin2hex(random_bytes(8));
    }

    public static function requestId(): string
    {
        return self::$requestId ?? self::traceId();
    }

    /** Valeur de l'en-tête traceparent à envoyer à un service appelé par cette requête. */
    public static function traceparent(): string
    {
        return '00-' . self::traceId() . '-' . self::spanId() . '-' . self::$flags;
    }

    /** @return array{request_id: string, trace_id?: string} */
    public static function context(): array
    {
        $context = ['request_id' => self::requestId()];

        if (self::requestId() !== self::traceId()) {
            $context['trace_id'] = self::traceId();
        }

        return $context;
    }

    /** Oublie la requête courante (fin de requête, tests). */
    public static function reset(): void
    {
        self::$traceId = null;
        self::$spanId = null;
        self::$flags = '00';
        self::$requestId = null;
        self::$active = false;
    }
}
