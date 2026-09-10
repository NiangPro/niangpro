<?php

namespace Niang\Core;

use Niang\Core\Http\Response;

class View
{
    /** @var array{0: string, 1: array}|null */
    private static ?array $pendingLayout = null;

    public static function make(string $view, array $data = []): Response
    {
        return Response::html(self::renderView($view, $data));
    }

    /** Rend une vue en chaîne (utilisé par make(), layout() et component()). */
    public static function renderView(string $view, array $data = []): string
    {
        $path = self::path($view);

        if (!file_exists($path)) {
            return "Vue introuvable : $view";
        }

        $previousLayout = self::$pendingLayout;
        self::$pendingLayout = null;

        $content = (function () use ($path, $data) {
            extract($data, EXTR_SKIP);
            ob_start();
            include $path;
            return ob_get_clean();
        })();

        if (self::$pendingLayout !== null) {
            [$layoutView, $layoutData] = self::$pendingLayout;
            self::$pendingLayout = $previousLayout;
            return self::renderView($layoutView, [...$layoutData, 'content' => $content]);
        }

        self::$pendingLayout = $previousLayout;
        return $content;
    }

    /** Appelé depuis une vue : le contenu déjà rendu est injecté dans $layoutView en tant que $content. */
    public static function useLayout(string $layoutView, array $data = []): void
    {
        self::$pendingLayout = [$layoutView, $data];
    }

    /** Inclut une vue partielle (ex: une carte, une liste d'erreurs) et retourne son HTML. */
    public static function component(string $view, array $data = []): string
    {
        return self::renderView($view, $data);
    }

    private static function path(string $view): string
    {
        return base_path('resources/views/' . str_replace('.', '/', $view) . '.php');
    }
}
