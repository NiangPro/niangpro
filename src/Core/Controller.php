<?php

namespace Niang\Core;

use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Validation\Validator;

abstract class Controller
{
    protected function view(string $view, array $data = []): Response
    {
        return View::make($view, $data);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }

    /**
     * Valide les données de la requête. Lève une ValidationException si une règle échoue
     * (interceptée par l'Application pour rediriger avec les erreurs et l'ancienne saisie).
     */
    protected function validate(Request $request, array $rules): array
    {
        return Validator::make($request->all(), $rules)->validate();
    }

    /**
     * Vérifie une règle définie via Gate::define(). Lève une AuthorizationException (403)
     * interceptée par l'Application si la règle n'autorise pas l'action.
     */
    protected function authorize(string $ability, mixed ...$args): void
    {
        if (Gate::denies($ability, ...$args)) {
            throw new AuthorizationException("Action non autorisée : $ability");
        }
    }
}
