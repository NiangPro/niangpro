<?php

namespace App\Controllers\Api\V1;

use App\Models\Note;
use App\Requests\NoteRequest;
use App\Resources\NoteResource;
use Niang\Core\Auth;
use Niang\Core\Controller;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Exceptions\NotFoundException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/** Notes de l'utilisateur authentifié. Une note d'un autre compte répond 404, jamais 403 : elle n'existe pas pour lui. */
class NoteController extends Controller
{
    /** Liste paginée (?page=2), la plus récente d'abord. */
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));

        return NoteResource::collection($this->mine()->orderBy('id', 'desc')->paginate((int) config('site.per_page', 20), $page))->toResponse();
    }

    public function store(NoteRequest $request): Response
    {
        $id = Note::forceCreate(['user_id' => Auth::id()] + $this->fields($request));

        return (new NoteResource(Note::find($id)))->toResponse(201);
    }

    public function show(string $id): Response
    {
        return (new NoteResource($this->find($id)))->toResponse();
    }

    public function update(NoteRequest $request, string $id): Response
    {
        $this->find($id);
        Note::update($id, $this->fields($request));

        return (new NoteResource($this->find($id)))->toResponse();
    }

    public function destroy(string $id): Response
    {
        $this->find($id);
        Note::destroy($id);

        return Response::html('', 204);
    }

    private function mine(): QueryBuilder
    {
        return Note::query()->where('user_id', Auth::id());
    }

    private function find(string $id): array
    {
        return $this->mine()->where('id', $id)->first() ?? throw new NotFoundException();
    }

    /** @return array{title: string, body: ?string, done: bool} */
    private function fields(NoteRequest $request): array
    {
        $data = $request->validated();

        return ['title' => $data['title'], 'body' => $data['body'] ?? null, 'done' => filter_var($data['done'] ?? false, FILTER_VALIDATE_BOOL)];
    }
}
