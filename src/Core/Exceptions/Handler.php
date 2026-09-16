<?php

namespace Niang\Core\Exceptions;

use Niang\Core\Auth;
use Niang\Core\Env;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Log;
use Niang\Core\Validation\ValidationException;
use Niang\Core\View;

/**
 * Point d'entrée unique pour transformer une exception en réponse HTTP. Application::handle()
 * délègue tout ici : plus aucune logique de rendu d'erreur éparpillée dans le routeur ou les
 * contrôleurs. En développement, détail complet (fichier, ligne, trace, requête, route, durée) ;
 * en production, jamais de secret exposé — juste un statut et un message sûr.
 */
class Handler
{
    public static function render(\Throwable $e, Request $request, float $startedAt): Response
    {
        if ($e instanceof ValidationException) {
            return self::renderValidation($e, $request);
        }

        if ($e instanceof HttpException) {
            return self::renderHttp($e, $request);
        }

        self::report($e);

        if ($e instanceof \PDOException) {
            return self::renderStatus($request, 500, 'Erreur de base de données.', $e, $startedAt);
        }

        return self::renderStatus($request, 500, 'Une erreur est survenue.', $e, $startedAt);
    }

    private static function renderValidation(ValidationException $e, Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['message' => 'La validation a échoué.', 'errors' => $e->errors], 422);
        }

        $back = $request->header('Referer', '/');

        return Response::redirect($back)
            ->with('errors', $e->errors)
            ->with('old', $request->all());
    }

    private static function renderHttp(HttpException $e, Request $request): Response
    {
        $status = $e->getStatusCode();

        if ($request->wantsJson()) {
            $response = Response::json(['message' => $e->getMessage()], $status);
        } else {
            $response = self::renderErrorView($status, $e->getMessage());
        }

        foreach ($e->getHeaders() as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }

    /** Utilisée pour les erreurs non prévues (500) : ajoute le détail de debug si APP_DEBUG=true. */
    private static function renderStatus(Request $request, int $status, string $safeMessage, \Throwable $e, float $startedAt): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['message' => self::isDebug() ? $e->getMessage() : $safeMessage], $status);
        }

        if (self::isDebug()) {
            return self::renderDebugPage($e, $request, $startedAt);
        }

        return self::renderErrorView($status, $safeMessage);
    }

    private static function renderErrorView(int $status, string $message): Response
    {
        $dedicated = base_path("resources/views/errors/$status.php");

        if (file_exists($dedicated)) {
            return View::make("errors.$status")->status($status);
        }

        if (file_exists(base_path('resources/views/errors/generic.php'))) {
            return View::make('errors.generic', ['status' => $status, 'message' => $message])->status($status);
        }

        return Response::html("<h1>$status</h1><p>" . htmlspecialchars($message) . '</p>', $status);
    }

    private static function report(\Throwable $e): void
    {
        Log::error($e->getMessage(), [
            'exception' => $e::class,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    private static function isDebug(): bool
    {
        return Env::get('APP_DEBUG', 'true') === 'true';
    }

    private static function renderDebugPage(\Throwable $e, Request $request, float $startedAt): Response
    {
        $message = htmlspecialchars($e->getMessage());
        $class = htmlspecialchars($e::class);
        $file = htmlspecialchars($e->getFile());
        $trace = htmlspecialchars($e->getTraceAsString());
        $method = htmlspecialchars($request->method);
        $uri = htmlspecialchars($request->uri);
        $params = $request->params ? htmlspecialchars(json_encode($request->params, JSON_UNESCAPED_UNICODE)) : '—';
        $user = Auth::check() ? htmlspecialchars((string) (Auth::user()['email'] ?? Auth::id())) : 'invité';
        $duration = number_format((hrtime(true) - $startedAt) / 1_000_000, 2);

        $html = <<<HTML
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>Erreur | NiangPro</title>
            <style>
                body { font-family: ui-monospace, monospace; background:#0c1917; color:#e6f4f2; padding:2rem; margin:0; }
                h1 { color:#fca5a5; font-size:1.4rem; margin:0 0 .25rem; }
                .message { font-size:1.15rem; margin:0 0 1.25rem; }
                .meta { display:grid; grid-template-columns:max-content 1fr; gap:.35rem 1.5rem; font-size:.85rem; color:#8fada8; margin-bottom:1.5rem; }
                .meta strong { color:#e6f4f2; font-weight:600; }
                pre { white-space:pre-wrap; background:#14302d; padding:1rem; border-radius:8px; font-size:.82rem; line-height:1.5; margin:0; }
            </style>
        </head>
        <body>
            <h1>{$class}</h1>
            <p class="message">{$message}</p>
            <div class="meta">
                <span>Fichier</span><strong>{$file}:{$e->getLine()}</strong>
                <span>Requête</span><strong>{$method} {$uri}</strong>
                <span>Paramètres de route</span><strong>{$params}</strong>
                <span>Utilisateur</span><strong>{$user}</strong>
                <span>Durée</span><strong>{$duration} ms</strong>
            </div>
            <pre>{$trace}</pre>
        </body>
        </html>
        HTML;

        return Response::html($html, 500);
    }
}
