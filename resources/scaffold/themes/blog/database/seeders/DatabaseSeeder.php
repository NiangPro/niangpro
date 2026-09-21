<?php

use App\Models\Post;
use App\Models\Tag;
use Niang\Core\Database\DB;
use Niang\Core\Database\Seeder;

/**
 * Articles de démonstration (contenu fictif) : à remplacer par les vôtres. Relancer
 * `niang db:seed` ne crée pas de doublons : un article dont le slug existe déjà est ignoré.
 *
 * Le corps est du texte simple : paragraphes séparés par une ligne vide, « ## » pour un
 * intertitre, « > » pour une citation, « - » pour une liste (voir App\Support\PostFormat).
 */
return new class extends Seeder {
    public function run(): void
    {
        $tagIds = [];

        foreach (['design', 'produit', 'accessibilite', 'html', 'performance', 'redaction', 'php'] as $name) {
            $existing = Tag::query()->where('name', $name)->first();
            $tagIds[$name] = $existing ? (int) $existing['id'] : (int) Tag::create(['name' => $name]);
        }

        foreach ($this->articles() as $article) {
            if (Post::query()->where('slug', $article['slug'])->first()) {
                continue;
            }

            $postId = (int) Post::create([
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'body' => $article['body'],
                'category' => $article['category'],
                'author' => $article['author'],
                'created_at' => $article['date'] . ' 09:00:00',
                'updated_at' => $article['date'] . ' 09:00:00',
            ]);

            foreach ($article['tags'] as $tag) {
                DB::statement('INSERT INTO post_tag (post_id, tag_id) VALUES (?, ?)', [$postId, $tagIds[$tag]]);
            }
        }
    }

    /** @return list<array<string, mixed>> du plus ancien au plus récent : l'id croissant suit la chronologie */
    private function articles(): array
    {
        return [
            [
                'slug' => 'un-lundi-a-l-atelier',
                'title' => "Un lundi à l'atelier : notre routine d'équipe",
                'category' => 'coulisses',
                'author' => 'Karim Diallo',
                'date' => '2026-07-16',
                'tags' => ['produit', 'redaction'],
                'excerpt' => "Pas d'outil miracle : une réunion de vingt minutes, un tableau papier et une règle simple pour que la semaine commence sans friction.",
                'body' => "Chaque lundi à 9 h 30, toute l'équipe se retrouve autour d'un tableau papier. Pas d'écran, pas de visioconférence : une feuille collée au mur et des marqueurs.\n\n## Trois colonnes, pas une de plus\n\nÀ faire, en cours, terminé. Nous avons essayé des tableaux à sept colonnes, avec des couleurs et des étiquettes. Ils ont tous fini par être abandonnés, faute d'être vraiment lus.\n\n## La règle des trois priorités\n\nChacun annonce au plus trois choses qu'il veut avoir terminées vendredi. Trois, pas quatre. Cette contrainte oblige à trancher, et c'est précisément le but.\n\n> Ce qui n'est pas écrit sur le mur n'existe pas cette semaine.\n\n## Vingt minutes, chrono en main\n\nLa réunion s'arrête quand le minuteur sonne, même si tout n'a pas été dit. Les sujets qui débordent partent en discussion à deux, au bon moment, avec les bonnes personnes.",
            ],
            [
                'slug' => 'choisir-ses-couleurs-sans-sacrifier-le-contraste',
                'title' => 'Choisir ses couleurs sans sacrifier le contraste',
                'category' => 'guides',
                'author' => 'Inès Marchand',
                'date' => '2026-07-25',
                'tags' => ['accessibilite', 'design'],
                'excerpt' => "Un beau nuancier ne suffit pas : encore faut-il que le texte reste lisible pour tout le monde. Voici comment vérifier, et corriger, en quelques minutes.",
                'body' => "On choisit souvent une palette pour son effet sur une maquette, puis on découvre trop tard que le gris clair sur fond blanc est illisible en plein soleil.\n\n## Le seuil à retenir\n\nLe rapport de contraste minimal recommandé est de 4,5 pour 1 pour le texte courant, et de 3 pour 1 pour les grands titres et les éléments d'interface. Ces valeurs viennent des règles d'accessibilité du web (WCAG), et elles sont mesurables.\n\n## Trois habitudes simples\n\n- Vérifier chaque paire texte/fond avec un outil de contraste, dès la palette.\n- Prévoir deux versions de chaque couleur d'accent : une pour les fonds, une plus foncée pour le texte.\n- Ne jamais transmettre une information par la couleur seule : ajouter une icône ou un mot.\n\n## Et le mode sombre ?\n\nLe même travail est à refaire : un teal éclatant sur fond noir se lit très bien, le même teal sur fond blanc beaucoup moins. Deux jeux de variables CSS suffisent, sélectionnés par prefers-color-scheme.",
            ],
            [
                'slug' => 'du-php-simple-sans-magie',
                'title' => "Du PHP simple, sans magie : ce qu'on y gagne",
                'category' => 'idees',
                'author' => 'Karim Diallo',
                'date' => '2026-08-03',
                'tags' => ['php', 'performance'],
                'excerpt' => "Moins de couches, moins de surprises. Retour sur trois ans à construire des sites avec un outillage volontairement minimal.",
                'body' => "Il y a trois ans, nous avons décidé de n'utiliser que ce que nous comprenions entièrement. Le résultat est moins spectaculaire qu'on pourrait le croire, et bien plus reposant.\n\n## Lire le code, c'est déjà déboguer\n\nQuand un comportement est surprenant, on ouvre le fichier concerné et on lit. Pas de générateur de code à deviner, pas de convention cachée. Un nouveau développeur est autonome en une journée.\n\n## Des performances gratuites\n\nMoins de couches, c'est moins de millisecondes. Nos pages se génèrent en quelques millisecondes sur un hébergement mutualisé à quelques euros par mois.\n\n## Ce que nous avons perdu\n\nQuelques raccourcis, oui. Mais chaque fois, nous avons préféré écrire vingt lignes claires plutôt que d'ajouter une dépendance dont nous ne maîtrisons ni les mises à jour ni les failles.",
            ],
            [
                'slug' => 'ecrire-pour-le-web',
                'title' => 'Écrire pour le web : trois règles qui changent tout',
                'category' => 'idees',
                'author' => 'Inès Marchand',
                'date' => '2026-08-12',
                'tags' => ['redaction', 'design'],
                'excerpt' => "On ne lit pas une page web comme un livre. Trois règles d'écriture pour être lu jusqu'au bout, sans sacrifier le fond.",
                'body' => "Les études de lecture sont formelles : sur écran, on parcourt avant de lire. Le texte doit donc se laisser survoler.\n\n## 1. La conclusion d'abord\n\nDites l'essentiel dans les deux premières phrases. Le lecteur pressé repart avec l'information, le lecteur curieux continue avec le contexte.\n\n## 2. Un paragraphe, une idée\n\nQuatre lignes maximum, et un intertitre qui annonce le contenu. Un intertitre vague (« Introduction ») ne sert à rien ; un intertitre précis fait gagner du temps.\n\n## 3. Des mots courants\n\nChaque mot rare est un obstacle. « Utiliser » vaut mieux que « mettre en œuvre » ; « aider » vaut mieux que « accompagner la montée en compétence ».\n\n> Écrire simplement n'est pas écrire pauvrement. C'est accepter de faire le travail à la place du lecteur.",
            ],
            [
                'slug' => 'coulisses-refonte-du-site-en-six-semaines',
                'title' => 'Coulisses : la refonte de notre site en six semaines',
                'category' => 'coulisses',
                'author' => 'Léa Fontaine',
                'date' => '2026-08-21',
                'tags' => ['design', 'produit'],
                'excerpt' => "Ce qui a marché, ce qui a coûté du temps, et la décision qui nous a fait gagner deux semaines : supprimer la moitié des pages.",
                'body' => "Notre ancien site comptait quarante-deux pages. Après analyse, six d'entre elles concentraient plus de 90 % des visites. Le reste était du bruit.\n\n## Semaine 1 : l'inventaire\n\nNous avons listé chaque page, ses visites et sa dernière mise à jour. La moitié n'avait pas été modifiée depuis plus de deux ans.\n\n## Semaines 2 à 4 : la maquette et le texte, ensemble\n\nPlutôt que de maquetter avec du faux texte, nous avons écrit les vrais contenus en même temps. Chaque désaccord de mise en page s'est réglé en relisant la phrase concernée.\n\n## Semaines 5 et 6 : la mise en ligne\n\nLes redirections des anciennes adresses ont pris une journée entière, et nous ne l'avions pas prévue. Si vous ne devez retenir qu'un conseil : commencez par elles.",
            ],
            [
                'slug' => 'un-site-rapide-sans-outil-de-build',
                'title' => 'Un site rapide sans aucun outil de build',
                'category' => 'guides',
                'author' => 'Karim Diallo',
                'date' => '2026-08-30',
                'tags' => ['performance', 'html'],
                'excerpt' => "Pas de bundler, pas de transpileur : un guide pour obtenir de bons scores de performance avec du HTML, du CSS et un peu de JavaScript.",
                'body' => "Un site rapide est d'abord un site léger. Or l'essentiel du poids d'une page vient rarement de ce que l'on écrit à la main.\n\n## Commencer par mesurer\n\nOuvrez l'onglet Réseau des outils de développement et triez par taille. Les trois plus gros fichiers expliquent en général l'essentiel du temps de chargement.\n\n## Les leviers qui comptent\n\n- Des images à la bonne taille, avec width et height renseignés pour éviter les sauts de mise en page.\n- Une seule feuille de style, mise en cache avec un paramètre de version.\n- Du JavaScript en fin de page, seulement là où il apporte quelque chose.\n- La compression gzip activée côté serveur.\n\n## Le mot de la fin\n\nAucune de ces optimisations ne demande d'outil de build. Elles demandent surtout de dire non à ce qui n'est pas indispensable.",
            ],
            [
                'slug' => 'accessibilite-par-ou-commencer',
                'title' => "Accessibilité : par où commencer quand on n'a que deux heures",
                'category' => 'guides',
                'author' => 'Inès Marchand',
                'date' => '2026-09-07',
                'tags' => ['accessibilite', 'html'],
                'excerpt' => "Un site totalement accessible demande du temps. Mais deux heures bien employées règlent déjà les problèmes qui excluent le plus de visiteurs.",
                'body' => "L'accessibilité est souvent perçue comme un chantier immense. En réalité, une poignée de vérifications couvre une grande partie des obstacles réels.\n\n## Naviguer au clavier\n\nPosez la souris et parcourez votre site avec la touche Tab. Voyez-vous toujours où vous êtes ? Pouvez-vous atteindre chaque lien, chaque bouton, chaque champ ? Sinon, commencez par là.\n\n## Utiliser le bon élément HTML\n\nUn bouton doit être un élément button, un lien un élément a, un titre un élément h1 à h6. Ces balises apportent gratuitement le comportement clavier et le sens que les lecteurs d'écran attendent.\n\n## Décrire les images\n\nChaque image informative a besoin d'un texte alternatif. Une image purement décorative reçoit un alt vide, pour être ignorée.\n\n## Les formulaires\n\nUn libellé visible pour chaque champ, associé par l'attribut for, et des messages d'erreur qui disent quoi corriger, pas seulement qu'il y a une erreur.",
            ],
            [
                'slug' => 'pourquoi-la-simplicite-gagne',
                'title' => 'Pourquoi la simplicité finit toujours par gagner',
                'category' => 'idees',
                'author' => 'Inès Marchand',
                'date' => '2026-09-14',
                'tags' => ['design', 'produit'],
                'excerpt' => "Chaque fonctionnalité ajoutée a un coût que l'on ne voit qu'après coup. Petit plaidoyer pour les produits qui savent s'arrêter.",
                'body' => "Il est toujours plus facile d'ajouter que de retirer. C'est pourquoi les produits grossissent, et pourquoi ceux qui résistent à la tentation se remarquent.\n\n## Le coût caché de chaque option\n\nUne option de plus, c'est une décision de plus pour l'utilisateur, un cas de plus à tester, une ligne de plus dans la documentation. Le bénéfice est visible immédiatement ; le coût se paie sur des années.\n\n## Retirer, c'est concevoir\n\nLes équipes que nous admirons ont toutes une habitude commune : avant d'ajouter, elles se demandent ce qu'elles peuvent enlever pour obtenir le même résultat.\n\n> La perfection est atteinte non pas lorsqu'il n'y a plus rien à ajouter, mais lorsqu'il n'y a plus rien à retirer.\n\n## En pratique\n\nFixez-vous un budget : une fonctionnalité ne peut entrer que si une autre sort, ou si elle remplace deux gestes de l'utilisateur par un seul. Vous serez surpris de tout ce qui ne mérite pas d'exister.",
            ],
        ];
    }
};
