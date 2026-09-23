<?php

namespace App\Controllers\Admin;

use App\Models\Post;
use App\Models\Tag;
use App\Support\AdminFormat;
use App\Support\AdminQuery;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/**
 * Articles : liste filtrable, rédaction, modification, suppression. Le corps suit le format de
 * App\Support\PostFormat (paragraphes, « ## » intertitre, « > » citation, « - » liste).
 */
class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $category = (string) $request->input('categorie', '');
        $search = trim((string) $request->input('q', ''));
        $where = ['1 = 1'];
        $bindings = [];

        if (array_key_exists($category, $this->categories())) {
            $where[] = 'category = ?';
            $bindings[] = $category;
        } else {
            $category = '';
        }

        if ($search !== '') {
            $where[] = "(title LIKE ? ESCAPE '!' OR author LIKE ? ESCAPE '!' OR slug LIKE ? ESCAPE '!')";
            array_push($bindings, ...array_fill(0, 3, AdminQuery::like($search)));
        }

        $posts = AdminQuery::paginate(
            'id, title, slug, excerpt, category, author, created_at, updated_at',
            'posts WHERE ' . implode(' AND ', $where),
            $bindings,
            'id DESC',
            10,
            (int) $request->input('page', 1)
        );

        return $this->view('admin/posts/index', [
            'posts' => $posts,
            'tags' => $this->tagsByPost(array_column($posts->items, 'id')),
            'category' => $category,
            'search' => $search,
            'categories' => $this->categories(),
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin/posts/form', $this->formData(null, []));
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request, null);

        if (is_string($data)) {
            return $this->redirect('/admin/articles/nouveau')->with('errors', ['slug' => [$data]])->with('old', $request->all());
        }

        $id = DB::transaction(function () use ($data, $request): int {
            $id = (int) Post::create([...$data, 'updated_at' => date('Y-m-d H:i:s')]);
            $this->syncTags($id, $request->input('tags', []));

            return $id;
        });

        return $this->redirect("/admin/articles/$id/modifier")->with('success', "Article « {$data['title']} » publié.");
    }

    public function edit(string $id): Response
    {
        $post = $this->find($id);

        return $this->view('admin/posts/form', $this->formData($post, array_column(Post::tags($post['id']), 'id')));
    }

    public function update(Request $request, string $id): Response
    {
        $post = $this->find($id);
        $data = $this->validated($request, (int) $post['id']);

        if (is_string($data)) {
            return $this->redirect("/admin/articles/{$post['id']}/modifier")->with('errors', ['slug' => [$data]])->with('old', $request->all());
        }

        DB::transaction(function () use ($post, $data, $request): void {
            Post::update($post['id'], [...$data, 'updated_at' => date('Y-m-d H:i:s')]);
            $this->syncTags((int) $post['id'], $request->input('tags', []));
        });

        return $this->redirect("/admin/articles/{$post['id']}/modifier")->with('success', 'Article enregistré.');
    }

    public function destroy(string $id): Response
    {
        $post = $this->find($id);

        DB::transaction(function () use ($post): void {
            DB::statement('DELETE FROM post_tag WHERE post_id = ?', [$post['id']]);
            DB::statement('DELETE FROM comments WHERE post_id = ?', [$post['id']]);
            Post::destroy($post['id']);
        });

        return $this->redirect('/admin/articles')->with('success', "Article « {$post['title']} » supprimé.");
    }

    private function find(string $id): array
    {
        return Post::find((int) $id) ?? abort(404, 'Article introuvable.');
    }

    /**
     * @param list<int|string> $selectedTags
     * @return array<string, mixed>
     */
    private function formData(?array $post, array $selectedTags): array
    {
        return [
            'post' => $post,
            'categories' => $this->categories(),
            'allTags' => Tag::query()->orderBy('name')->get(),
            'selectedTags' => array_map('intval', $selectedTags),
        ];
    }

    /**
     * Données prêtes à enregistrer, ou le message d'erreur si le slug est déjà pris (il peut être
     * déduit du titre : la règle unique ne peut donc pas s'appliquer avant).
     *
     * @return array<string, mixed>|string
     */
    private function validated(Request $request, ?int $ignoreId): array|string
    {
        $data = $this->validate($request, [
            'title' => 'required|string|min:3|max:200',
            'slug' => ['nullable', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', 'max:200'],
            'excerpt' => 'nullable|string|max:400',
            'body' => 'required|string|min:20',
            'category' => 'required|in:' . implode(',', array_keys($this->categories())),
            'author' => 'nullable|string|max:120',
            'published_at' => 'nullable|date_format:Y-m-d',
        ], [
            'slug.regex' => 'Lettres minuscules, chiffres et tirets uniquement (ex. : mon-article).',
        ], [
            'title' => 'titre', 'body' => 'contenu', 'category' => 'catégorie', 'excerpt' => 'chapeau', 'published_at' => 'date de publication',
        ]);

        $slug = ($data['slug'] ?? '') !== '' ? $data['slug'] : AdminFormat::slug($data['title']);
        $taken = Post::query()->where('slug', $slug)->first();

        if ($slug === '' || ($taken && (int) $taken['id'] !== $ignoreId)) {
            return "L'adresse « $slug » est déjà utilisée par un autre article.";
        }

        $fields = [
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => ($data['excerpt'] ?? '') !== '' ? $data['excerpt'] : null,
            'body' => $data['body'],
            'category' => $data['category'],
            'author' => ($data['author'] ?? '') !== '' ? $data['author'] : (string) (Auth::user()['name'] ?? ''),
        ];

        // Date de publication : celle saisie, sinon maintenant pour un nouvel article ; un article
        // modifié sans date garde la sienne.
        if (($data['published_at'] ?? '') !== '') {
            $fields['created_at'] = $data['published_at'] . ' 09:00:00';
        } elseif ($ignoreId === null) {
            $fields['created_at'] = date('Y-m-d H:i:s');
        }

        return $fields;
    }

    private function syncTags(int $postId, mixed $tagIds): void
    {
        $valid = array_column(Tag::all(), 'id');
        $tagIds = array_unique(array_map('intval', is_array($tagIds) ? $tagIds : []));

        DB::statement('DELETE FROM post_tag WHERE post_id = ?', [$postId]);

        foreach ($tagIds as $tagId) {
            if (in_array($tagId, array_map('intval', $valid), true)) {
                DB::statement('INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)', [$postId, $tagId]);
            }
        }
    }

    /**
     * @param list<int|string> $postIds
     * @return array<int, list<string>> id d'article => noms de ses tags
     */
    private function tagsByPost(array $postIds): array
    {
        if (!$postIds) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($postIds), '?'));
        $tags = [];

        foreach (DB::select("SELECT post_tag.post_id, tags.name FROM post_tag INNER JOIN tags ON tags.id = post_tag.tag_id WHERE post_tag.post_id IN ($placeholders) ORDER BY tags.name", $postIds) as $row) {
            $tags[(int) $row['post_id']][] = $row['name'];
        }

        return $tags;
    }

    /** @return array<string, string> slug => libellé */
    private function categories(): array
    {
        return array_map(static fn (array $c): string => $c['label'], (array) config('site.categories', []));
    }
}
