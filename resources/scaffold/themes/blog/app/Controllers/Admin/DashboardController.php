<?php

namespace App\Controllers\Admin;

use App\Models\Post;
use App\Support\AdminFormat;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Response;

/** Vue d'ensemble du blog : volume de publication, catégories, thèmes, derniers articles. */
class DashboardController extends Controller
{
    /** Nombre de mois affichés sur la courbe des publications. */
    private const CHART_MONTHS = 6;

    public function index(): Response
    {
        $posts = DB::select('SELECT id, body, category, author, created_at FROM posts');
        $words = array_sum(array_map(static fn (array $p): int => str_word_count(strip_tags((string) $p['body'])), $posts));
        $thisMonth = count(array_filter($posts, static fn (array $p): bool => str_starts_with((string) $p['created_at'], date('Y-m'))));
        $authors = array_unique(array_filter(array_column($posts, 'author')));

        return $this->view('admin/dashboard', [
            'stats' => [
                'posts' => count($posts),
                'thisMonth' => $thisMonth,
                'tags' => (int) (DB::selectOne('SELECT COUNT(*) AS n FROM tags')['n'] ?? 0),
                'authors' => count($authors),
                'readingMinutes' => $posts ? (int) round($words / count($posts) / 200) : 0,
            ],
            'chart' => $this->monthlyPosts($posts),
            'categories' => $this->categoryBreakdown($posts),
            'topTags' => DB::select(
                'SELECT tags.name, COUNT(post_tag.post_id) AS n FROM tags LEFT JOIN post_tag ON post_tag.tag_id = tags.id '
                . 'GROUP BY tags.id, tags.name ORDER BY n DESC, tags.name ASC LIMIT 6'
            ),
            'latest' => Post::query()->orderBy('id', 'desc')->limit(5)->get(),
        ]);
    }

    /**
     * Articles publiés par mois, regroupés en PHP (mêmes résultats en SQLite et MySQL).
     *
     * @param list<array<string, mixed>> $posts
     * @return list<array{label: string, value: int, display: string}>
     */
    private function monthlyPosts(array $posts): array
    {
        $months = [];

        for ($i = self::CHART_MONTHS - 1; $i >= 0; $i--) {
            $months[date('Y-m', strtotime(date('Y-m-01') . " -$i months"))] = 0;
        }

        foreach ($posts as $post) {
            $month = substr((string) $post['created_at'], 0, 7);

            if (array_key_exists($month, $months)) {
                $months[$month]++;
            }
        }

        $points = [];

        foreach ($months as $month => $count) {
            $points[] = ['label' => AdminFormat::month($month), 'value' => $count, 'display' => $count . ' article' . ($count > 1 ? 's' : '')];
        }

        return $points;
    }

    /**
     * @param list<array<string, mixed>> $posts
     * @return list<array{label: string, value: int, display: string, href: string}>
     */
    private function categoryBreakdown(array $posts): array
    {
        $counts = array_count_values(array_map('strval', array_filter(array_column($posts, 'category'))));
        $rows = [];

        foreach ((array) config('site.categories', []) as $slug => $category) {
            $n = $counts[$slug] ?? 0;
            $rows[] = ['label' => $category['label'], 'value' => $n, 'display' => (string) $n, 'href' => '/admin/articles?categorie=' . $slug];
        }

        return $rows;
    }
}
