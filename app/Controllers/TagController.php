<?php

namespace App\Controllers;

use App\Models\Tag;
use Niang\Core\Controller;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/** Démontre $router->resource() : les 7 méthodes REST conventionnelles. */
class TagController extends Controller
{
    public function index(): Response
    {
        return $this->json(Tag::all());
    }

    public function create(): Response
    {
        return $this->json(['fields' => ['name']]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, ['name' => 'required|string|min:2']);
        $id = Tag::create($data);

        return $this->json(['id' => $id], 201);
    }

    public function show(string $id): Response
    {
        $tag = Tag::find($id);

        if (!$tag) {
            abort(404, 'Tag introuvable.');
        }

        return $this->json($tag);
    }

    public function edit(string $id): Response
    {
        return $this->show($id);
    }

    public function update(Request $request, string $id): Response
    {
        $data = $this->validate($request, ['name' => 'required|string|min:2']);
        Tag::update($id, $data);

        return $this->json(['updated' => true]);
    }

    public function destroy(string $id): Response
    {
        Tag::destroy($id);

        return $this->json(['deleted' => true]);
    }
}
