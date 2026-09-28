<?php

namespace App\Controllers;

use App\Billing\Billing;
use App\Models\Project;
use App\Support\Team;
use Niang\Core\Controller;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/** Projets de l'organisation courante : Project est $tenantScoped, aucun filtre à écrire ici. */
class ProjectController extends Controller
{
    public function index(): Response
    {
        return $this->view('team/projects', [
            'projects' => Project::query()->orderBy('name')->get(),
            'canCreate' => Billing::allows('projects'),
            'limit' => Billing::limit('projects'),
        ]);
    }

    public function store(Request $request): Response
    {
        if (!Billing::allows('projects')) {
            return $this->redirect(Team::url('/projets'))->with('error', 'Limite de projets de votre plan atteinte : passez au plan supérieur.');
        }

        $data = $this->validate($request, ['name' => 'required|string|min:2|max:120', 'description' => 'nullable|string|max:2000'], [], ['name' => 'nom']);
        Project::create($data);

        return $this->redirect(Team::url('/projets'))->with('success', 'Projet créé.');
    }

    public function destroy(string $id): Response
    {
        Project::find($id) ?? throw new NotFoundException();
        Project::forceDestroy($id);

        return $this->redirect(Team::url('/projets'))->with('success', 'Projet supprimé.');
    }
}
