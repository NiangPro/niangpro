<?php

namespace App\Controllers\Admin;

use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Response;

/** Catégories du blog, définies dans config/site.php, avec leur nombre d'articles. */
class CategoryController extends Controller
{
    public function index(): Response
    {
        $stats = [];

        foreach (DB::select('SELECT category, COUNT(*) AS posts, MAX(created_at) AS last_at FROM posts WHERE category IS NOT NULL GROUP BY category') as $row) {
            $stats[$row['category']] = $row;
        }

        return $this->view('admin/categories', ['categories' => (array) config('site.categories', []), 'stats' => $stats]);
    }
}
