<?php

namespace App\Controllers;

use App\Models\Post;
use App\Models\Tag;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Response;

/**
 * Pages du blog. La liste paginée des articles (/blog) est servie par PostController::page, déjà
 * présent dans le projet ; ce contrôleur ajoute l'accueil, l'article, les catégories et les tags.
 */
class BlogController extends Controller
{
    public function home(): Response
    {
        $latest = Post::with('tags')->orderBy('id', 'desc')->limit(7)->get();

        return $this->view('blog/home', [
            'featured' => $latest[0] ?? null,
            'posts' => array_slice($latest, 1),
            'categories' => $this->categoryCounts(),
        ]);
    }

    public function show(string $slug): Response
    {
        $post = Post::with('tags')->where('slug', $slug)->first();

        if (!$post) {
            abort(404, 'Article introuvable.');
        }

        // Articles proches : d'abord la même catégorie, complétés par les plus récents.
        $related = Post::query()->where('id', '!=', $post['id'])->where('category', $post['category'])->orderBy('id', 'desc')->limit(3)->get();

        if (count($related) < 3) {
            $seen = array_merge([$post['id']], array_column($related, 'id'));
            $more = Post::query()->orderBy('id', 'desc')->limit(6)->get();
            $related = array_slice([...$related, ...array_filter($more, static fn (array $p): bool => !in_array($p['id'], $seen))], 0, 3);
        }

        return $this->view('blog/show', ['post' => $post, 'related' => $related]);
    }

    /** Catégories et tags, avec le nombre d'articles de chacun. */
    public function tags(): Response
    {
        $tags = DB::select(
            'SELECT tags.name, COUNT(post_tag.post_id) AS posts_count FROM tags '
            . 'LEFT JOIN post_tag ON post_tag.tag_id = tags.id GROUP BY tags.id, tags.name ORDER BY posts_count DESC, tags.name ASC'
        );

        return $this->view('blog/tags', ['tags' => $tags, 'categories' => $this->categoryCounts()]);
    }

    public function tag(string $name): Response
    {
        $tag = Tag::query()->where('name', $name)->first();

        if (!$tag) {
            abort(404, 'Thème introuvable.');
        }

        $posts = DB::select(
            'SELECT posts.* FROM posts INNER JOIN post_tag ON post_tag.post_id = posts.id WHERE post_tag.tag_id = ? ORDER BY posts.id DESC',
            [$tag['id']]
        );

        return $this->view('blog/archive', ['heading' => '#' . $tag['name'], 'lead' => 'Tous les articles sur ce thème.', 'posts' => $posts]);
    }

    public function category(string $slug): Response
    {
        $category = config('site.categories.' . $slug);

        if (!$category) {
            abort(404, 'Catégorie introuvable.');
        }

        return $this->view('blog/archive', [
            'heading' => $category['label'],
            'lead' => $category['text'],
            'posts' => Post::query()->where('category', $slug)->orderBy('id', 'desc')->get(),
        ]);
    }

    public function about(): Response
    {
        return $this->view('pages/about');
    }

    /** @return array<string, int> slug de catégorie => nombre d'articles (0 compris) */
    private function categoryCounts(): array
    {
        $counts = array_fill_keys(array_keys(config('site.categories', [])), 0);

        foreach (DB::select('SELECT category, COUNT(*) AS total FROM posts WHERE category IS NOT NULL GROUP BY category') as $row) {
            if (array_key_exists($row['category'], $counts)) {
                $counts[$row['category']] = (int) $row['total'];
            }
        }

        return $counts;
    }
}
