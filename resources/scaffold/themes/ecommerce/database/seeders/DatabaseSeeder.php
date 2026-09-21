<?php

use App\Models\Product;
use Niang\Core\Database\Seeder;

/**
 * Catalogue de démonstration : produits fictifs, à remplacer par les vôtres.
 * Relancer `niang db:seed` ne crée pas de doublons (le slug est unique et déjà présent = ignoré).
 */
return new class extends Seeder {
    public function run(): void
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
};
