<?php

namespace App\Controllers\Admin;

use App\Models\Product;
use App\Support\AdminFormat;
use App\Support\AdminQuery;
use Niang\Core\Controller;
use Niang\Core\Database\DB;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/**
 * Catalogue : produits (CRUD), vue par catégorie et gestion du stock. Les prix sont saisis en
 * euros et stockés en centimes (voir App\Models\Product).
 */
class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $category = (string) $request->input('categorie', '');
        $search = trim((string) $request->input('q', ''));
        $where = ['1 = 1'];
        $bindings = [];

        if (array_key_exists($category, $this->categoryLabels())) {
            $where[] = 'category = ?';
            $bindings[] = $category;
        } else {
            $category = '';
        }

        if ($search !== '') {
            $where[] = "(name LIKE ? ESCAPE '!' OR slug LIKE ? ESCAPE '!')";
            array_push($bindings, AdminQuery::like($search), AdminQuery::like($search));
        }

        return $this->view('admin/products/index', [
            'products' => AdminQuery::paginate('*', 'products WHERE ' . implode(' AND ', $where), $bindings, 'id DESC', 12, (int) $request->input('page', 1)),
            'category' => $category,
            'search' => $search,
            'categories' => $this->categoryLabels(),
        ]);
    }

    public function create(): Response
    {
        return $this->view('admin/products/form', ['product' => null, 'categories' => $this->categoryLabels()]);
    }

    public function store(Request $request): Response
    {
        $data = $this->validated($request, null);

        if (is_string($data)) {
            return $this->redirect('/admin/produits/nouveau')->with('errors', ['slug' => [$data]])->with('old', $request->all());
        }

        Product::create([...$data, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->redirect('/admin/produits')->with('success', "Produit « {$data['name']} » ajouté au catalogue.");
    }

    public function edit(string $id): Response
    {
        return $this->view('admin/products/form', ['product' => $this->find($id), 'categories' => $this->categoryLabels()]);
    }

    public function update(Request $request, string $id): Response
    {
        $product = $this->find($id);
        $data = $this->validated($request, (int) $product['id']);

        if (is_string($data)) {
            return $this->redirect("/admin/produits/{$product['id']}/modifier")->with('errors', ['slug' => [$data]])->with('old', $request->all());
        }

        Product::update($product['id'], [...$data, 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->redirect('/admin/produits')->with('success', "Produit « {$data['name']} » mis à jour.");
    }

    /** Les commandes passées gardent le nom et le prix du produit (copiés à l'achat) : rien à nettoyer. */
    public function destroy(string $id): Response
    {
        $product = $this->find($id);
        Product::destroy($product['id']);

        return $this->redirect('/admin/produits')->with('success', "Produit « {$product['name']} » supprimé.");
    }

    public function categories(): Response
    {
        $stats = [];

        foreach (DB::select('SELECT category, COUNT(*) AS products, COALESCE(SUM(stock), 0) AS stock FROM products GROUP BY category') as $row) {
            $stats[$row['category']] = $row;
        }

        return $this->view('admin/products/categories', ['categories' => $this->categoryList(), 'stats' => $stats]);
    }

    public function stock(Request $request): Response
    {
        $lowOnly = $request->input('filtre') === 'faible';
        $query = Product::query()->orderBy('stock')->orderBy('name');

        if ($lowOnly) {
            $query->where('stock', '<=', Product::LOW_STOCK);
        }

        return $this->view('admin/products/stock', [
            'products' => $query->get(),
            'lowOnly' => $lowOnly,
            'units' => (int) Product::query()->sum('stock'),
            'value' => (int) (DB::selectOne('SELECT COALESCE(SUM(stock * price_cents), 0) AS v FROM products')['v'] ?? 0),
            'outOfStock' => Product::query()->where('stock', 0)->count(),
            'low' => Product::query()->where('stock', '<=', Product::LOW_STOCK)->count(),
        ]);
    }

    public function updateStock(Request $request, string $id): Response
    {
        $product = $this->find($id);
        $data = $this->validate($request, ['stock' => 'required|integer|min:0|max:100000']);

        Product::update($product['id'], ['stock' => (int) $data['stock'], 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->redirect('/admin/stock' . ($request->input('filtre') === 'faible' ? '?filtre=faible' : ''))
            ->with('success', "Stock de « {$product['name']} » : {$data['stock']} unité(s).");
    }

    private function find(string $id): array
    {
        return Product::find((int) $id) ?? abort(404, 'Produit introuvable.');
    }

    /**
     * Données du formulaire prêtes à enregistrer, ou le message d'erreur si le slug est déjà pris
     * (il peut être déduit du nom : la règle unique ne peut donc pas s'appliquer avant).
     *
     * @return array<string, mixed>|string
     */
    private function validated(Request $request, ?int $ignoreId): array|string
    {
        $data = $this->validate($request, [
            'name' => 'required|string|min:2|max:160',
            'slug' => ['nullable', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', 'max:160'],
            'category' => 'required|in:' . implode(',', array_keys($this->categoryLabels())),
            'description' => 'required|string|min:10',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0|max:100000',
            'image' => 'nullable|string|max:255',
        ], [
            'slug.regex' => 'Lettres minuscules, chiffres et tirets uniquement (ex. : tasse-en-gres).',
        ], [
            'name' => 'nom', 'category' => 'catégorie', 'price' => 'prix', 'old_price' => 'prix barré',
        ]);

        $slug = ($data['slug'] ?? '') !== '' ? $data['slug'] : AdminFormat::slug($data['name']);
        $taken = Product::query()->where('slug', $slug)->first();

        if ($slug === '' || ($taken && (int) $taken['id'] !== $ignoreId)) {
            return "L'adresse « $slug » est déjà utilisée par un autre produit.";
        }

        $oldPrice = ($data['old_price'] ?? '') !== '' ? (int) round((float) $data['old_price'] * 100) : null;

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'category' => $data['category'],
            'description' => $data['description'],
            'price_cents' => (int) round((float) $data['price'] * 100),
            'old_price_cents' => $oldPrice ?: null,
            'stock' => (int) $data['stock'],
            'image' => ($data['image'] ?? '') !== '' ? $data['image'] : null,
            'featured' => $request->input('featured') ? 1 : 0,
        ];
    }

    /** @return array<string, string> slug => libellé */
    private function categoryLabels(): array
    {
        return array_map(static fn (array $c): string => $c['label'], $this->categoryList());
    }

    /** @return array<string, array{label: string, icon: string, text: string}> */
    private function categoryList(): array
    {
        return (array) config('site.categories', []);
    }
}
