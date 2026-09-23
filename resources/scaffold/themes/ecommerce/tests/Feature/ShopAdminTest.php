<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Csrf;
use Niang\Core\Database\Seeder;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Livré avec le thème « boutique » : le tableau de bord montre les données de démonstration, et
 * l'administrateur gère produits, stock et commandes. L'accès lui-même est couvert par AdminAccessTest.
 */
class ShopAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Seeder $seeder */
        $seeder = require base_path('database/seeders/DatabaseSeeder.php');
        $seeder->run();

        Auth::login(User::query()->where('email', 'admin@example.com')->first());
    }

    /** @return array<string, string> */
    private function productForm(array $overrides = []): array
    {
        return [
            '_token' => Csrf::token(),
            'name' => 'Lampe en céramique émaillée',
            'slug' => '',
            'category' => 'maison',
            'description' => 'Pied en céramique tournée, abat-jour en lin. Ampoule E27 non fournie.',
            'price' => '59.90',
            'old_price' => '',
            'stock' => '8',
            'image' => '',
            ...$overrides,
        ];
    }

    public function test_the_dashboard_shows_the_demo_activity(): void
    {
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Ventes des 14 derniers jours')
            ->assertSee('DEMO-1013')
            ->assertSee('Meilleures ventes')
            ->assertSee('Stock faible');
    }

    public function test_the_seeder_does_not_duplicate_demo_orders(): void
    {
        $count = count(Order::all());
        (require base_path('database/seeders/DatabaseSeeder.php'))->run();

        $this->assertCount($count, Order::all());
    }

    public function test_orders_can_be_filtered_searched_and_moved_forward(): void
    {
        $this->get('/admin/commandes?statut=pending')->assertOk()->assertSee('DEMO-1013')->assertDontSee('DEMO-1001');
        $this->get('/admin/commandes?q=DEMO-1001')->assertOk()->assertSee('DEMO-1001')->assertDontSee('DEMO-1013');

        $order = Order::findByReference('DEMO-1013');
        $this->get('/admin/commandes/' . $order['id'])->assertOk()->assertSee('Client et livraison');

        $this->post('/admin/commandes/' . $order['id'] . '/statut', ['_token' => Csrf::token(), 'status' => 'shipped'])
            ->assertRedirect('/admin/commandes/' . $order['id']);
        $this->assertSame('shipped', Order::find($order['id'])['status']);

        $this->post('/admin/commandes/' . $order['id'] . '/statut', ['_token' => Csrf::token(), 'status' => 'perdue']);
        $this->assertSame('shipped', Order::find($order['id'])['status']);
    }

    public function test_a_product_can_be_created_edited_and_deleted(): void
    {
        $this->get('/admin/produits/nouveau')->assertOk()->assertSee('Nouveau produit');

        $this->post('/admin/produits', $this->productForm())->assertRedirect('/admin/produits');
        $product = Product::findBySlug('lampe-en-ceramique-emaillee');
        $this->assertNotNull($product);
        $this->assertSame(5990, (int) $product['price_cents']);
        $this->get('/boutique/lampe-en-ceramique-emaillee')->assertOk();

        $this->get('/admin/produits/' . $product['id'] . '/modifier')->assertOk()->assertSee('Lampe en céramique émaillée');
        $this->post('/admin/produits/' . $product['id'], $this->productForm(['slug' => 'lampe-ceramique', 'price' => '49', 'old_price' => '59.90', 'featured' => '1']))
            ->assertRedirect('/admin/produits');
        $product = Product::find($product['id']);
        $this->assertSame('lampe-ceramique', $product['slug']);
        $this->assertSame(5990, (int) $product['old_price_cents']);
        $this->assertSame(1, (int) $product['featured']);

        $this->post('/admin/produits/' . $product['id'] . '/supprimer', ['_token' => Csrf::token()])->assertRedirect('/admin/produits');
        $this->assertNull(Product::find($product['id']));
    }

    public function test_a_slug_already_used_by_another_product_is_refused(): void
    {
        $this->post('/admin/produits', $this->productForm(['slug' => 'tasse-gres']))->assertRedirect('/admin/produits/nouveau');

        $this->assertCount(1, Product::where('slug', 'tasse-gres'));
    }

    public function test_stock_can_be_adjusted(): void
    {
        $product = Product::findBySlug('theiere-fonte-emaillee');

        $this->get('/admin/stock')->assertOk()->assertSee('Valeur du stock');
        $this->post('/admin/stock/' . $product['id'], ['_token' => Csrf::token(), 'stock' => '3'])->assertRedirect('/admin/stock');

        $this->assertSame(3, (int) Product::find($product['id'])['stock']);
        $this->get('/admin/stock?filtre=faible')->assertOk()->assertSee('Théière en fonte émaillée');
    }

    public function test_customers_list_their_orders(): void
    {
        $this->get('/admin/clients')->assertOk()->assertSee('Awa Diop')->assertDontSee('mailto:admin@example.com');
        $this->get('/admin/clients?q=moussa')->assertOk()->assertSee('Moussa Ndiaye')->assertDontSee('Awa Diop');
    }

    public function test_the_shop_header_links_the_admin_to_the_dashboard(): void
    {
        $this->get('/')->assertOk()->assertSee('href="/admin"');
    }
}
