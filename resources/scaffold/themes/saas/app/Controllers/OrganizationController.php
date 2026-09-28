<?php

namespace App\Controllers;

use App\Billing\Billing;
use App\Models\Membership;
use App\Models\Project;
use App\Support\Team;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

class OrganizationController extends Controller
{
    /** Les organisations de l'utilisateur ; une seule : on y va directement. */
    public function index(Request $request): Response
    {
        $organizations = Team::organizationsOf(Auth::id());

        if (count($organizations) === 1 && $request->input('liste') === null) {
            return $this->redirect('/o/' . $organizations[0]['slug']);
        }

        return $this->view('organizations/index', ['organizations' => $organizations]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, ['name' => 'required|string|min:2|max:80'], [], ['name' => 'nom de l\'organisation']);
        $organization = Team::create($data['name'], Auth::id());

        return $this->redirect('/o/' . $organization['slug'])->with('success', "Organisation « {$organization['name']} » créée.");
    }

    /** Tableau de bord de l'organisation courante. */
    public function dashboard(): Response
    {
        return $this->view('team/dashboard', [
            'organization' => Team::current(),
            'plan' => Billing::plan(Team::current()),
            'projects' => Project::query()->count(),
            'members' => Membership::query()->count(),
            'recent' => Project::query()->orderBy('id', 'desc')->limit(5)->get(),
        ]);
    }
}
