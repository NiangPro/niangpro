<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Niang\Core\Csrf;
use Niang\Core\Database\DB;
use Niang\Core\Database\Seeder;
use Niang\Core\Session;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Livré avec le thème « boutique » : chaque page visiteur répond, le parcours d'achat va jusqu'à la
 * confirmation (sans paiement : voir le TODO de CheckoutController), et rien n'est chargé depuis
 * l'extérieur. RefreshDatabase annule les commandes créées entre deux tests.
 */
class ShopSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Seeder $seeder */
        $seeder = require base_path('database/seeders/DatabaseSeeder.php');
        $seeder->run();

        // Les commandes de démonstration (DEMO-*, pour le tableau de bord) gêneraient les assertions
        // sur LA commande passée par chaque test : ShopAdminTest, lui, les garde.
        DB::statement('DELETE FROM order_items');
        DB::statement('DELETE FROM orders');
    }

    private function productId(string $slug): int
    {
        return (int) Product::findBySlug($slug)['id'];
    }

    private function addToCart(string $slug, int $quantity = 1): void
    {
        $this->post('/panier/ajouter', [
            '_token' => Csrf::token(),
            'product_id' => $this->productId($slug),
            'quantity' => $quantity,
        ])->assertRedirect('/panier');
    }

    /** @return array<string, string> */
    private function shippingDetails(): array
    {
        return [
            '_token' => Csrf::token(),
            'name' => 'Awa Diop',
            'email' => 'awa@example.test',
            'address' => '12 rue des Halles',
            'postal_code' => '44000',
            'city' => 'Nantes',
        ];
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function pages(): array
    {
        return [
            'accueil' => ['/', 'Des objets simples'],
            'boutique' => ['/boutique', 'Toute la boutique'],
            'à propos' => ['/a-propos', 'Nos engagements'],
            'faq' => ['/faq', 'Livraison et retours'],
            'cgv' => ['/cgv', 'Droit de rétractation'],
            'mentions légales' => ['/mentions-legales', 'Éditeur du site'],
            'contact' => ['/contact', 'Envoyer le message'],
            'connexion' => ['/login', 'Se connecter'],
            'inscription' => ['/register', 'Créer mon compte'],
            'panier vide' => ['/panier', 'Votre panier est vide'],
        ];
    }

    /** @dataProvider pages */
    public function test_every_public_page_responds_with_its_content(string $uri, string $expected): void
    {
        $this->get($uri)->assertOk()->assertSee($expected);
    }

    /** @dataProvider pages */
    public function test_pages_load_nothing_from_an_external_host_and_have_no_inline_script_or_image_without_alt(string $uri): void
    {
        $html = $this->get($uri)->content();

        $this->assertDoesNotMatchRegularExpression('#(?:src|href)="https?://#', $html, 'Ressource externe (CDN) détectée.');
        $this->assertDoesNotMatchRegularExpression('#<script(?![^>]*\bsrc=)#', $html, 'Script inline : bloqué par la CSP par défaut.');
        $this->assertDoesNotMatchRegularExpression('#<img(?![^>]*\balt=)#', $html, 'Image sans attribut alt.');
    }

    public function test_the_seeder_loads_the_demo_catalogue_without_duplicating_it_when_run_twice(): void
    {
        $count = count(Product::all());
        $this->assertGreaterThanOrEqual(5, $count);
        $this->assertLessThanOrEqual(10, $count);

        /** @var Seeder $seeder */
        $seeder = require base_path('database/seeders/DatabaseSeeder.php');
        $seeder->run();

        $this->assertCount($count, Product::all());
    }

    public function test_home_page_features_products_and_promotions(): void
    {
        $this->get('/')->assertOk()->assertSee('Carnet relié à la main')->assertSee('Plaid en lin lavé')->assertSee('−25 %');
    }

    public function test_every_product_page_responds_and_offers_to_add_to_the_cart(): void
    {
        foreach (Product::all() as $product) {
            $this->get('/boutique/' . $product['slug'])->assertOk()->assertSee($product['name'])->assertSee('Ajouter au panier');
        }
    }

    public function test_an_unknown_product_is_a_themed_404(): void
    {
        $this->get('/boutique/inexistant')->assertStatus(404)->assertSee('site-header');
    }

    public function test_catalogue_filters_by_category(): void
    {
        $this->call('GET', '/boutique', ['categorie' => 'papeterie'])->assertOk()->assertSee('Carnet relié à la main')->assertDontSee('Bougie parfumée Figuier');
    }

    public function test_catalogue_ignores_an_unknown_category_instead_of_failing(): void
    {
        $this->call('GET', '/boutique', ['categorie' => 'nimporte-quoi'])->assertOk()->assertSee('Bougie parfumée Figuier');
    }

    public function test_catalogue_searches_by_name(): void
    {
        $this->call('GET', '/boutique', ['q' => 'tasse'])->assertOk()->assertSee('Tasse en grès artisanale')->assertDontSee('Plaid en lin lavé');
        $this->call('GET', '/boutique', ['q' => 'zzzz'])->assertOk()->assertSee('Aucun produit trouvé');
    }

    public function test_catalogue_sorts_by_price(): void
    {
        $ascending = $this->call('GET', '/boutique', ['tri' => 'prix-asc'])->content();
        $descending = $this->call('GET', '/boutique', ['tri' => 'prix-desc'])->content();

        $this->assertLessThan(strpos($ascending, 'Plaid en lin lavé'), strpos($ascending, 'Trousse en toile cirée'));
        $this->assertLessThan(strpos($descending, 'Trousse en toile cirée'), strpos($descending, 'Plaid en lin lavé'));
    }

    public function test_adding_a_product_shows_it_in_the_cart_with_the_total(): void
    {
        $this->addToCart('tasse-gres', 2);

        $this->get('/panier')->assertOk()->assertSee('Tasse en grès artisanale')->assertSee("36,00\u{00A0}€");
    }

    public function test_adding_the_same_product_twice_adds_up_its_quantity(): void
    {
        $this->addToCart('tasse-gres');
        $this->addToCart('tasse-gres');

        $this->assertSame([$this->productId('tasse-gres') => 2], \App\Support\Cart::raw());
    }

    public function test_quantity_cannot_exceed_the_stock(): void
    {
        Product::update($this->productId('tasse-gres'), ['stock' => 3]);

        $this->addToCart('tasse-gres', 10);

        $this->assertSame(3, \App\Support\Cart::count());
    }

    public function test_a_sold_out_product_cannot_be_added(): void
    {
        Product::update($this->productId('tasse-gres'), ['stock' => 0]);

        $this->post('/panier/ajouter', ['_token' => Csrf::token(), 'product_id' => $this->productId('tasse-gres')])->assertRedirect('/boutique');
        $this->assertSame(0, \App\Support\Cart::count());
    }

    public function test_the_quantity_can_be_changed_and_a_line_removed(): void
    {
        $this->addToCart('tasse-gres');
        $id = $this->productId('tasse-gres');

        $this->post("/panier/$id/modifier", ['_token' => Csrf::token(), 'quantity' => 4])->assertRedirect('/panier');
        $this->assertSame(4, \App\Support\Cart::count());

        $this->post("/panier/$id/retirer", ['_token' => Csrf::token()])->assertRedirect('/panier');
        $this->assertSame(0, \App\Support\Cart::count());
    }

    public function test_setting_the_quantity_to_zero_removes_the_line(): void
    {
        $this->addToCart('tasse-gres');

        $this->post('/panier/' . $this->productId('tasse-gres') . '/modifier', ['_token' => Csrf::token(), 'quantity' => 0]);

        $this->assertSame(0, \App\Support\Cart::count());
    }

    public function test_cart_actions_require_a_csrf_token(): void
    {
        $this->post('/panier/ajouter', ['product_id' => $this->productId('tasse-gres')])->assertStatus(419);
    }

    public function test_shipping_is_charged_below_the_threshold_and_free_above(): void
    {
        $this->addToCart('tasse-gres');
        $this->get('/panier')->assertSee("4,90\u{00A0}€")->assertSee('pour la livraison offerte');

        $this->addToCart('plaid-lin-lave');
        $this->get('/panier')->assertSee('Offerte')->assertDontSee('pour la livraison offerte');
    }

    public function test_checkout_redirects_to_the_cart_when_it_is_empty(): void
    {
        $this->get('/commande')->assertRedirect('/panier');
        $this->post('/commande', $this->shippingDetails())->assertRedirect('/panier');
    }

    public function test_checkout_page_shows_the_summary_and_the_payment_notice(): void
    {
        $this->addToCart('tasse-gres');

        $this->get('/commande')->assertOk()->assertSee('Livraison')->assertSee('Mode démonstration')->assertSee('Tasse en grès artisanale');
    }

    public function test_checkout_rejects_missing_shipping_details(): void
    {
        $this->addToCart('tasse-gres');

        $this->post('/commande', ['_token' => Csrf::token(), 'name' => 'A'])->assertRedirect();
        $this->assertSame([], Order::all());
    }

    public function test_a_valid_order_is_recorded_decrements_the_stock_and_empties_the_cart(): void
    {
        $this->addToCart('tasse-gres', 2);
        $id = $this->productId('tasse-gres');
        $stockBefore = (int) Product::find($id)['stock'];

        $response = $this->post('/commande', $this->shippingDetails());

        $order = Order::all()[0];
        $response->assertRedirect('/commande/confirmation/' . $order['reference']);
        $this->assertMatchesRegularExpression('/^CMD-[A-F0-9]{8}$/', $order['reference']);
        $this->assertSame('pending', $order['status']);
        $this->assertSame(3600, (int) $order['subtotal_cents']);
        $this->assertSame(490, (int) $order['shipping_cents']);
        $this->assertSame(4090, (int) $order['total_cents']);
        $this->assertNull($order['user_id']);

        $items = Order::items($order['id']);
        $this->assertCount(1, $items);
        $this->assertSame('Tasse en grès artisanale', $items[0]['name']);
        $this->assertSame(2, (int) $items[0]['quantity']);

        $this->assertSame($stockBefore - 2, (int) Product::find($id)['stock']);
        $this->assertSame(0, \App\Support\Cart::count());
    }

    public function test_the_confirmation_page_is_shown_to_the_customer_who_just_ordered(): void
    {
        $this->addToCart('tasse-gres');
        $this->post('/commande', $this->shippingDetails());
        $reference = Order::all()[0]['reference'];

        $this->get("/commande/confirmation/$reference")->assertOk()->assertSee($reference)->assertSee('Merci pour votre commande')->assertSee('Nantes');
    }

    public function test_the_confirmation_page_is_hidden_from_anyone_else(): void
    {
        $this->addToCart('tasse-gres');
        $this->post('/commande', $this->shippingDetails());
        $reference = Order::all()[0]['reference'];

        Session::forget('last_order');

        $this->get("/commande/confirmation/$reference")->assertStatus(404);
    }

    public function test_the_ordered_quantity_is_capped_at_the_stock_left_when_it_dropped_since_the_cart_was_filled(): void
    {
        $this->addToCart('tasse-gres', 5);
        Product::update($this->productId('tasse-gres'), ['stock' => 2]);

        // Le panier est ramené au stock restant : la commande porte sur 2 unités, jamais sur 5.
        $this->post('/commande', $this->shippingDetails());

        $this->assertSame(2, (int) Order::items(Order::all()[0]['id'])[0]['quantity']);
        $this->assertSame(0, (int) Product::find($this->productId('tasse-gres'))['stock']);
    }

    public function test_my_orders_requires_a_login(): void
    {
        $this->get('/compte/commandes')->assertRedirect('/login');
    }

    public function test_a_customer_can_register_order_and_find_the_order_in_their_account(): void
    {
        $this->post('/register', [
            '_token' => Csrf::token(),
            'name' => 'Awa Diop',
            'email' => 'awa@example.test',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])->assertRedirect('/');

        $this->get('/compte/commandes')->assertOk()->assertSee('Aucune commande pour l\'instant');

        $this->addToCart('tasse-gres');
        $this->post('/commande', $this->shippingDetails());
        $order = Order::all()[0];

        $this->assertNotNull($order['user_id']);
        $this->get('/compte/commandes')->assertOk()->assertSee($order['reference'])->assertSee('En attente de paiement');
    }

    public function test_login_fails_with_a_wrong_password(): void
    {
        $this->post('/login', ['_token' => Csrf::token(), 'email' => 'inconnu@example.test', 'password' => 'x'])->assertRedirect('/login');
    }

    public function test_contact_form_accepts_a_valid_message_and_requires_a_csrf_token(): void
    {
        $this->post('/contact', ['_token' => Csrf::token(), 'name' => 'Awa', 'email' => 'awa@example.test', 'message' => 'Où en est ma commande ?'])->assertRedirect('/contact');
        $this->post('/contact', ['name' => 'Awa'])->assertStatus(419);
    }

    public function test_unknown_pages_show_the_themed_404(): void
    {
        $this->get('/page-inexistante')->assertStatus(404)->assertSee('Cette page n\'existe pas');
    }
}
