<?php

namespace Tests\Feature;

use Niang\Core\Csrf;
use Niang\Core\Database\DB;
use Niang\Core\Database\Seeder;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Audit de performance : CheckoutController::store() relisait le stock avec un Product::find()
 * PAR ligne du panier (N+1) avant d'être corrigé pour une seule requête (whereIn). Mesuré via
 * DB::queryCount() (voir tests/Database/EagerLoadingTest.php pour le même principe côté noyau),
 * pas seulement relu : un panier de 3 articles distincts ne doit pas coûter 2 requêtes de lecture
 * de plus qu'un panier à un seul article pour cette étape.
 */
class CheckoutQueryCountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Seeder $seeder */
        $seeder = require base_path('database/seeders/DatabaseSeeder.php');
        $seeder->run();

        DB::statement('DELETE FROM order_items');
        DB::statement('DELETE FROM orders');
    }

    public function test_checking_out_does_not_query_stock_once_per_distinct_product(): void
    {
        $oneItemCount = $this->checkoutQueryCount(['bougie-figuier']);
        $threeItemsCount = $this->checkoutQueryCount(['plaid-lin-lave', 'coussin-velours-cotele', 'vase-verre-souffle']);

        // Chaque article ajoute des écritures inévitables (OrderItem::create + Product::update,
        // 2 requêtes par article) : la différence attendue est proportionnelle à ces écritures
        // (2 articles de plus = au plus 4 requêtes de plus), jamais aux lectures de stock — avec
        // l'ancien Product::find() par ligne, 2 articles de plus auraient ajouté 2 SELECT de plus,
        // portant la différence à 6+.
        $this->assertLessThanOrEqual($oneItemCount + 4, $threeItemsCount);
    }

    /** @param list<string> $slugs */
    private function checkoutQueryCount(array $slugs): int
    {
        foreach ($slugs as $slug) {
            $productId = (int) \App\Models\Product::findBySlug($slug)['id'];

            $this->post('/panier/ajouter', [
                '_token' => Csrf::token(),
                'product_id' => $productId,
            ])->assertRedirect('/panier');
        }

        DB::resetQueryCount();

        $this->post('/commande', [
            '_token' => Csrf::token(),
            'name' => 'Awa Diop',
            'email' => 'awa@example.test',
            'address' => '12 rue des Halles',
            'postal_code' => '44000',
            'city' => 'Nantes',
        ])->assertRedirect();

        return DB::queryCount();
    }
}
