<?php

namespace App\Controllers;

use App\Models\Product;
use Niang\Core\Controller;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;

/** Vitrine de la boutique : accueil, catalogue, fiche produit et pages d'information. */
class ShopController extends Controller
{
    /** Critères de tri acceptés par /boutique?tri=... : clé d'URL => [colonne, sens]. */
    private const SORTS = [
        'nouveautes' => ['id', 'desc'],
        'prix-asc' => ['price_cents', 'asc'],
        'prix-desc' => ['price_cents', 'desc'],
        'nom' => ['name', 'asc'],
    ];

    public function home(): Response
    {
        return $this->view('shop/home', [
            'featured' => Product::query()->where('featured', 1)->orderBy('id', 'desc')->limit(4)->get(),
            'onSale' => Product::query()->whereNotNull('old_price_cents')->limit(3)->get(),
        ]);
    }

    /** Filtre par catégorie (?categorie=), recherche (?q=) et tri (?tri=) ; toute valeur inconnue est ignorée. */
    public function catalog(Request $request): Response
    {
        $category = (string) $request->input('categorie', '');
        $category = array_key_exists($category, config('site.categories', [])) ? $category : '';
        $sort = array_key_exists((string) $request->input('tri'), self::SORTS) ? (string) $request->input('tri') : 'nouveautes';
        $search = trim((string) $request->input('q', ''));

        $query = Product::query();

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($search !== '') {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        [$column, $direction] = self::SORTS[$sort];

        return $this->view('shop/catalog', [
            'products' => $query->orderBy($column, $direction)->get(),
            'category' => $category,
            'sort' => $sort,
            'search' => $search,
        ]);
    }

    public function show(string $slug): Response
    {
        $product = Product::findBySlug($slug);

        if (!$product) {
            abort(404);
        }

        $related = Product::query()
            ->where('category', $product['category'])
            ->where('id', '!=', $product['id'])
            ->limit(4)
            ->get();

        return $this->view('shop/product', ['product' => $product, 'related' => $related]);
    }

    public function about(): Response
    {
        return $this->view('pages/about');
    }

    public function faq(): Response
    {
        return $this->view('pages/faq');
    }

    public function terms(): Response
    {
        return $this->view('pages/terms');
    }

    public function legal(): Response
    {
        return $this->view('pages/legal');
    }
}
