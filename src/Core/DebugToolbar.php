<?php

namespace Niang\Core;

use Niang\Core\Database\DB;
use Niang\Core\Http\Response;

/**
 * Barre de debug injectée en bas des réponses HTML quand APP_DEBUG=true : temps de réponse,
 * nombre de requêtes SQL (DB::queryCount(), remis à zéro à chaque requête par
 * Application::handle()), pic mémoire. Jamais injectée sur une réponse non-HTML (JSON, etc.), ni
 * sans balise </body> où s'accrocher — pour ne jamais corrompre le corps de la réponse.
 */
class DebugToolbar
{
    public static function inject(Response $response, float $startedAt): Response
    {
        if (!self::shouldInject($response)) {
            return $response;
        }

        $durationMs = number_format((hrtime(true) - $startedAt) / 1_000_000, 2);
        $queries = DB::queryCount();
        $memoryMb = number_format(memory_get_peak_usage(true) / 1_048_576, 2);

        $bar = self::render($durationMs, $queries, $memoryMb, $response->getStatus());

        return $response->content(str_replace('</body>', $bar . '</body>', $response->getContent()));
    }

    private static function shouldInject(Response $response): bool
    {
        return Env::get('APP_DEBUG', 'true') === 'true'
            && str_starts_with((string) $response->getHeader('Content-Type'), 'text/html')
            && str_contains($response->getContent(), '</body>');
    }

    private static function render(string $durationMs, int $queries, string $memoryMb, int $status): string
    {
        return <<<HTML
            <div style="position:fixed;bottom:0;left:0;right:0;background:#0c1917;color:#e6f4f2;
                border-top:1px solid #2a4d49;font:12px/1.6 ui-monospace,monospace;padding:6px 14px;
                z-index:99999;display:flex;gap:20px;">
                <span style="color:#2dd4bf;">⏱ {$durationMs} ms</span>
                <span style="color:#2dd4bf;">🗄 {$queries} requête(s) SQL</span>
                <span style="color:#2dd4bf;">🧠 {$memoryMb} MB</span>
                <span style="color:#facc15;">↩ {$status}</span>
            </div>
            HTML;
    }
}
