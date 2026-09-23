<?php

namespace App\Controllers\Admin;

use App\Models\Tag;
use App\Support\AdminFormat;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/** Tags (thèmes transverses aux catégories) : liste avec usage, création, suppression. */
class TagController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin/tags', [
            'tags' => DB::select(
                'SELECT tags.id, tags.name, COUNT(post_tag.post_id) AS posts_count FROM tags '
                . 'LEFT JOIN post_tag ON post_tag.tag_id = tags.id GROUP BY tags.id, tags.name ORDER BY posts_count DESC, tags.name ASC'
            ),
        ]);
    }

    /** Le nom sert d'URL (/tags/{nom}) : il est ramené à des minuscules sans accents ni espaces. */
    public function store(Request $request): Response
    {
        $data = $this->validate($request, ['name' => 'required|string|min:2|max:40'], [], ['name' => 'nom']);
        $name = AdminFormat::slug($data['name']);

        if ($name === '' || Tag::query()->where('name', $name)->first()) {
            return $this->redirect('/admin/tags')
                ->with('errors', ['name' => ["Le tag « $name » existe déjà ou n'est pas valide."]])
                ->with('old', ['name' => $data['name']]);
        }

        Tag::create(['name' => $name]);

        return $this->redirect('/admin/tags')->with('success', "Tag « $name » créé.");
    }

    /** Retire le tag des articles qui le portent ; les articles eux-mêmes ne sont pas touchés. */
    public function destroy(string $id): Response
    {
        $tag = Tag::find((int) $id) ?? abort(404, 'Tag introuvable.');

        DB::transaction(function () use ($tag): void {
            DB::statement('DELETE FROM post_tag WHERE tag_id = ?', [$tag['id']]);
            Tag::destroy($tag['id']);
        });

        return $this->redirect('/admin/tags')->with('success', "Tag « {$tag['name']} » supprimé.");
    }
}
