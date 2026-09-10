<?php

namespace App\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Niang\Core\Cache;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

class PostController extends Controller
{
    public function index(): Response
    {
        $posts = Cache::remember('posts.index', 60, function () {
            return array_map(function (array $post) {
                $post['comments'] = Post::comments($post['id']);
                $post['tags'] = Post::tags($post['id']);
                return $post;
            }, Post::all());
        });

        return $this->json($posts);
    }

    public function page(Request $request): Response
    {
        $paginator = Post::paginate(2, (int) $request->input('page', 1));

        $items = array_map(fn (array $post) => [...$post, 'tags' => Post::tags($post['id'])], $paginator->items);

        return $this->view('posts/index', [
            'posts' => $items,
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
            return $this->json(['message' => 'Introuvable.'], 404);
        }

        $this->authorize('delete-post', $post);

        Post::destroy($id);
        Cache::forget('posts.index');

        return $this->json(['deleted' => true]);
    }
}
