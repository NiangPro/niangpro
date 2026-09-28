<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use Niang\Core\Database\Seeder;
use Niang\Core\Hash;

/**
 * Données de démonstration, fictives, à remplacer par les vôtres : le compte administrateur de
 * test (AdminUserSeeder), le catalogue, puis quelques clients et commandes pour que le tableau de
 * bord de /admin ait quelque chose à montrer dès le premier lancement.
 * Relancer `niang db:seed` ne crée pas de doublons (slug, email ou référence déjà présents = ignorés).
 */
return new class extends Seeder {
    public function run(): void
    {
        (require __DIR__ . '/AdminUserSeeder.php')->run();

        $this->products();
        $this->demoOrders();
    }

    private function products(): void
    {
        $products = [
            ['Bougie parfumée Figuier', 'bougie-figuier', 'maison', 2400, null, 34, true, "Cire de soja coulée à la main, mèche en coton. Un parfum de figuier vert et de bois de cèdre, pour 45 heures de combustion."],
            ['Plaid en lin lavé', 'plaid-lin-lave', 'maison', 8900, 11900, 12, true, "Lin français prélavé, doux dès la première utilisation. Se froisse joliment, se lave en machine à 30 °C. 130 × 170 cm."],
            ['Coussin en velours côtelé', 'coussin-velours-cotele', 'maison', 3800, null, 25, false, "Housse déhoussable en coton côtelé, garnissage en fibres recyclées. 45 × 45 cm, plusieurs coloris."],
            ['Vase soliflore en verre soufflé', 'vase-verre-souffle', 'maison', 2900, null, 18, true, "Soufflé à la bouche dans un atelier de Meurthe-et-Moselle : chaque pièce est légèrement différente. Hauteur 22 cm."],
            ['Tasse en grès artisanale', 'tasse-gres', 'cuisine', 1800, null, 60, true, "Tournée à la main, émaillée à l'intérieur comme à l'extérieur. Passe au lave-vaisselle et au micro-ondes. 30 cl."],
            ['Théière en fonte émaillée', 'theiere-fonte-emaillee', 'cuisine', 6400, 7900, 9, false, "Garde le thé chaud pendant une heure. Infuseur en inox amovible, contenance 80 cl."],
            ['Set de 4 sous-verres en liège', 'sous-verres-liege', 'cuisine', 1600, null, 45, false, "Liège du Portugal, naturellement antidérapant et isolant. Lavables à l'éponge. Diamètre 10 cm."],
            ['Carnet relié à la main', 'carnet-relie-main', 'papeterie', 1900, null, 40, true, "160 pages de papier ivoire 100 g, cousues et reliées à la main. Couverture en carton recyclé. A5."],
            ['Trousse en toile cirée', 'trousse-toile-cire', 'papeterie', 1500, 2000, 30, false, "Toile de coton enduite, doublure imperméable, fermeture zippée. Se glisse dans tous les sacs."],
        ];

        foreach ($products as [$name, $slug, $category, $price, $oldPrice, $stock, $featured, $description]) {
            if (Product::findBySlug($slug)) {
                continue;
            }

            Product::create([
                'name' => $name,
                'slug' => $slug,
                'category' => $category,
                'description' => $description,
                'price_cents' => $price,
                'old_price_cents' => $oldPrice,
                'stock' => $stock,
                'featured' => $featured ? 1 : 0,
            ]);
        }
    }

    /**
     * Commandes réparties sur les trois dernières semaines, références DEMO-*. Le stock n'est pas
     * décrémenté : ces ventes n'ont jamais eu lieu.
     */
    private function demoOrders(): void
    {
        if (Order::findByReference('DEMO-1001')) {
            return;
        }

        $customers = [];

        foreach ([['Awa Diop', 'awa.diop@example.test'], ['Moussa Ndiaye', 'moussa.ndiaye@example.test'], ['Claire Martin', 'claire.martin@example.test']] as [$name, $email]) {
            $existing = User::query()->where('email', $email)->first();
            $customers[] = $existing ? $existing : User::find(User::forceCreate([
                'name' => $name,
                'email' => $email,
                // Mot de passe aléatoire jamais communiqué : ces comptes ne servent qu'à la démonstration.
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'role' => 'user',
            ]));
        }

        $products = array_values(Product::query()->orderBy('id')->get());
        $guests = [['Julien Robert', 'julien.robert@example.test', 'Lyon', '69002'], ['Fatou Sall', 'fatou.sall@example.test', 'Bordeaux', '33000']];
        // [jours écoulés, client (index, ou null = invité), statut, [[produit, quantité], ...]]
        $plan = [
            [20, 0, 'shipped', [[0, 1], [4, 2]]],
            [18, null, 'shipped', [[1, 1]]],
            [15, 1, 'shipped', [[7, 2], [8, 1]]],
            [13, 2, 'cancelled', [[5, 1]]],
            [11, 0, 'shipped', [[2, 1], [0, 1]]],
            [9, null, 'shipped', [[4, 4]]],
            [8, 1, 'paid', [[3, 1], [6, 2]]],
            [6, 2, 'shipped', [[1, 1], [7, 1]]],
            [5, null, 'paid', [[0, 2]]],
            [3, 0, 'paid', [[5, 1], [4, 1]]],
            [2, 1, 'pending', [[8, 2], [7, 1]]],
            [1, null, 'pending', [[2, 2]]],
            [0, 2, 'pending', [[1, 1], [3, 1]]],
        ];

        foreach ($plan as $i => [$daysAgo, $customerIndex, $status, $lines]) {
            $customer = $customerIndex !== null ? $customers[$customerIndex] : null;
            $guest = $guests[$i % count($guests)];
            $date = date('Y-m-d H:i:s', strtotime("-$daysAgo days") - ($i * 2437) % 30000);
            $subtotal = 0;
            $items = [];

            foreach ($lines as [$productIndex, $quantity]) {
                $product = $products[$productIndex % count($products)];
                $subtotal += (int) $product['price_cents'] * $quantity;
                $items[] = [$product, $quantity];
            }

            $shipping = Cart::shipping($subtotal);
            $orderId = Order::forceCreate([
                'user_id' => $customer['id'] ?? null,
                'reference' => 'DEMO-' . (1001 + $i),
                'name' => $customer['name'] ?? $guest[0],
                'email' => $customer['email'] ?? $guest[1],
                'address' => (12 + $i) . ' rue des Halles',
                'postal_code' => $customer ? '44000' : $guest[3],
                'city' => $customer ? 'Nantes' : $guest[2],
                'subtotal_cents' => $subtotal,
                'shipping_cents' => $shipping,
                'total_cents' => $subtotal + $shipping,
                'status' => $status,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            foreach ($items as [$product, $quantity]) {
                OrderItem::forceCreate([
                    'order_id' => $orderId,
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'unit_price_cents' => $product['price_cents'],
                    'quantity' => $quantity,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }
};
