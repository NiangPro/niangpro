<?php

namespace App\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Resources\PostResource;
use Niang\Core\Cache;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

class PostController extends Controller
{
    /** Démo P0 #12 : JsonResource + pagination -> {"data": [...], "meta": {...}, "links": {...}}. */
    public function apiIndex(Request $request): Response
    {
        $paginator = Post::with(['comments', 'tags'])->paginate(10, (int) $request->input('page', 1));

        return PostResource::collection($paginator)->toResponse();
    }

    public function index(): Response
    {
        // Post::with(['comments', 'tags'])->get() : 3 requêtes au total quel que soit le nombre de
        // posts (1 pour les posts, 1 pour tous les commentaires via whereIn, 1 pour tous les tags),
        // plutôt que 2N+1 en interrogeant chaque relation post par post.
        $posts = Cache::remember('posts.index', 60, fn () => Post::with(['comments', 'tags'])->get());

        return $this->json($posts);
    }

    /**
     * Les plus récents d'abord. 2 articles par page par défaut (pour que la pagination se voie sur les
     * trois articles de la démo) ; un thème de blog règle 'posts_per_page' dans config/site.php.
     */
    public function page(Request $request): Response
    {
        $paginator = Post::with('tags')
            ->orderBy('id', 'desc')
            ->paginate((int) config('site.posts_per_page', 2), (int) $request->input('page', 1));

        return $this->view('posts/index', [
            'posts' => $paginator->items,
            'paginator' => $paginator,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, [
            'title' => 'required|string|min:3',
            'body' => 'required|string|min:5',
        ]);

        $postId = DB::transaction(function () use ($data) {
            $postId = Post::create($data);

            Comment::create([
                'post_id' => $postId,
                'body' => 'Premier commentaire automatique.',
            ]);

            return $postId;
        });

        Cache::forget('posts.index');

        return $this->json(['id' => $postId], 201);
    }

    public function destroy(string $id): Response
    {
        $post = Post::find($id);

        if (!$post) {
            abort(404, 'Article introuvable.');
        }

        $this->authorize('post.delete', $post);

        Post::destroy($id);
        Cache::forget('posts.index');

        return $this->json(['deleted' => true]);
    }
}
