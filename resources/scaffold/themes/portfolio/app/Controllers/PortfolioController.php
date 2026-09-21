<?php

namespace App\Controllers;

use Niang\Core\Controller;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/**
 * Pages du portfolio. Les projets vivent dans config/site.php : aucune base de données.
 */
class PortfolioController extends Controller
{
    public function home(): Response
    {
        return $this->view('portfolio/home', ['featured' => array_slice(config('site.projects'), 0, 3)]);
    }

    /** ?categorie=<slug> filtre la liste ; une catégorie inconnue est ignorée. */
    public function projects(Request $request): Response
    {
        $category = (string) $request->input('categorie', '');
        $category = array_key_exists($category, config('site.categories', [])) ? $category : '';

        $projects = array_values(array_filter(
            config('site.projects'),
            static fn (array $project): bool => $category === '' || $project['category'] === $category
        ));

        return $this->view('portfolio/projects', ['projects' => $projects, 'category' => $category]);
    }

    public function project(string $slug): Response
    {
        $projects = config('site.projects');
        $index = array_search($slug, array_column($projects, 'slug'), true);

        if ($index === false) {
            abort(404, 'Projet introuvable.');
        }

        return $this->view('portfolio/project', [
            'project' => $projects[$index],
            // Navigation circulaire : le dernier projet renvoie au premier.
            'previous' => $projects[($index - 1 + count($projects)) % count($projects)],
            'next' => $projects[($index + 1) % count($projects)],
        ]);
    }

    public function about(): Response
    {
        return $this->view('pages/about');
    }
}
