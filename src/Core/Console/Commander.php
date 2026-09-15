<?php

namespace Niang\Core\Console;

use Niang\Core\Cache;
use Niang\Core\Database\Migrator;
use Niang\Core\Database\Seeder;
use Niang\Core\Env;
use Niang\Core\Queue;
use Niang\Core\RouteCache;
use Niang\Core\Router;

class Commander
{
    public function __construct(private string $basePath)
    {
        Env::load($basePath . '/.env');
    }

    public function run(array $argv): void
    {
        $command = $argv[1] ?? 'help';
        $arg = $argv[2] ?? null;

        match ($command) {
            'serve' => $this->serve($arg),
            'make:controller' => $this->makeController($arg),
            'make:model' => $this->makeModel($arg),
            'make:migration' => $this->makeMigration($arg),
            'make:seeder' => $this->makeSeeder($arg),
            'migrate' => $this->migrate(),
            'migrate:rollback' => $this->migrateRollback(),
            'migrate:fresh' => $this->migrateFresh(),
            'db:seed' => $this->dbSeed($arg),
            'route:cache' => $this->routeCache(),
            'route:clear' => $this->routeClear(),
            'route:list' => $this->routeList(),
            'make:middleware' => $this->makeMiddleware($arg),
            'make:request' => $this->makeRequest($arg),
            'tinker' => $this->tinker(),
            'key:generate' => $this->keyGenerate(),
            'queue:work' => $this->queueWork(),
            'cache:clear' => $this->cacheClear(),
            'optimize' => $this->optimize(),
            'new' => $this->newProject($arg),
            'np:install' => $this->npInstall(),
            default => $this->help(),
        };
    }

    private function serve(?string $address): void
    {
        $address = $address ?: '127.0.0.1:8000';
        echo "NiangPro démarre sur http://$address (Ctrl+C pour arrêter)\n";
        passthru(sprintf(
            'php -S %s -t %s',
            escapeshellarg($address),
            escapeshellarg($this->basePath . '/public')
        ));
    }

    private function makeController(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:controller NomController\n";
            return;
        }

        $name = str_ends_with($name, 'Controller') ? $name : $name . 'Controller';
        $path = $this->basePath . "/app/Controllers/$name.php";

        if (file_exists($path)) {
            echo "Le contrôleur $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        namespace App\Controllers;

        use Niang\Core\Controller;
        use Niang\Core\Http\Request;
        use Niang\Core\Http\Response;

        class {$name} extends Controller
        {
            public function index(Request \$request): Response
            {
                return \$this->json(['message' => 'OK']);
            }
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Contrôleur créé : app/Controllers/$name.php\n";
    }

    private function makeModel(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:model NomModel\n";
            return;
        }

        $path = $this->basePath . "/app/Models/$name.php";

        if (file_exists($path)) {
            echo "Le modèle $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        namespace App\Models;

        use Niang\Core\Database\Model;

        class {$name} extends Model
        {
            // protected static string \$table = 'ma_table';
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Modèle créé : app/Models/$name.php\n";
    }

    private function makeMigration(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:migration create_table_name\n";
            return;
        }

        $dir = $this->basePath . '/database/migrations';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $name));
        $fileName = "{$timestamp}_{$slug}.php";
        $path = "$dir/$fileName";

        $table = 'table_name';
        if (preg_match('/^create_(.+)_table$/', $slug, $matches)) {
            $table = $matches[1];
        }

        $stub = <<<PHP
        <?php

        use Niang\Core\Database\Migration;
        use Niang\Core\Database\Schema;

        return new class extends Migration {
            public function up(): void
            {
                Schema::create('{$table}', function (\$table) {
                    \$table->id();
                    \$table->timestamps();
                });
            }

            public function down(): void
            {
                Schema::drop('{$table}');
            }
        };

        PHP;

        file_put_contents($path, $stub);
        echo "Migration créée : database/migrations/$fileName\n";
    }

    private function makeSeeder(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:seeder NomSeeder\n";
            return;
        }

        $name = str_ends_with($name, 'Seeder') ? $name : $name . 'Seeder';
        $dir = $this->basePath . '/database/seeders';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "$dir/$name.php";

        if (file_exists($path)) {
            echo "Le seeder $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        use Niang\Core\Database\Seeder;

        return new class extends Seeder {
            public function run(): void
            {
                // \App\Models\MonModele::create([...]);
            }
        };

        PHP;

        file_put_contents($path, $stub);
        echo "Seeder créé : database/seeders/$name.php\n";
    }

    private function migrate(): void
    {
        $applied = $this->migrator()->run();

        if (!$applied) {
            echo "Rien à migrer.\n";
            return;
        }

        foreach ($applied as $name) {
            echo "Migré : $name\n";
        }
    }

    private function migrateRollback(): void
    {
        $rolledBack = $this->migrator()->rollback();

        if (!$rolledBack) {
            echo "Rien à annuler.\n";
            return;
        }

        foreach ($rolledBack as $name) {
            echo "Annulé : $name\n";
        }
    }

    private function migrateFresh(): void
    {
        $applied = $this->migrator()->fresh();
        echo "Base réinitialisée.\n";

        foreach ($applied as $name) {
            echo "Migré : $name\n";
        }
    }

    private function migrator(): Migrator
    {
        return new Migrator($this->basePath . '/database/migrations');
    }

    private function dbSeed(?string $name): void
    {
        $fileName = $name ? (str_ends_with($name, 'Seeder') ? $name : $name . 'Seeder') : 'DatabaseSeeder';
        $path = $this->basePath . "/database/seeders/$fileName.php";

        if (!file_exists($path)) {
            echo "Seeder introuvable : database/seeders/$fileName.php\n";
            return;
        }

        /** @var Seeder $seeder */
        $seeder = require $path;
        $seeder->run();

        echo "Seeder exécuté : $fileName\n";
    }

    private function routeCache(): void
    {
        $router = new Router();
        require $this->basePath . '/routes/web.php';

        $total = count($router->routes());
        $cached = RouteCache::store($router->routes(), Router::namedRoutes());
        $skipped = $total - $cached;

        echo "Routes mises en cache : $cached\n";

        if ($skipped > 0) {
            echo "$skipped route(s) à closure ignorée(s) (non sérialisables) — utilisez un contrôleur pour les mettre en cache.\n";
        }
    }

    private function routeClear(): void
    {
        RouteCache::clear();
        echo "Cache de routes supprimé.\n";
    }

    private function routeList(): void
    {
        $router = new Router();
        require $this->basePath . '/routes/web.php';

        printf("%-7s %-30s %-20s %s\n", 'MÉTHODE', 'URI', 'NOM', 'ACTION');

        foreach ($router->routes() as $route) {
            $action = match (true) {
                $route['action'] instanceof \Closure => '{closure}',
                is_array($route['action']) => $route['action'][0] . '@' . $route['action'][1],
                default => (string) $route['action'],
            };

            printf(
                "%-7s %-30s %-20s %s\n",
                $route['method'],
                $route['domain'] ? "[{$route['domain']}]{$route['uri']}" : $route['uri'],
                $route['name'] ?? '',
                $action
            );
        }
    }

    private function makeMiddleware(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:middleware NomMiddleware\n";
            return;
        }

        $path = $this->basePath . "/app/Middleware/$name.php";

        if (file_exists($path)) {
            echo "Le middleware $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        namespace App\Middleware;

        use Niang\Core\Http\Request;
        use Niang\Core\Http\Response;
        use Niang\Core\Middleware;

        class {$name} implements Middleware
        {
            public function handle(Request \$request, \Closure \$next): Response
            {
                return \$next(\$request);
            }
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Middleware créé : app/Middleware/$name.php\n";
    }

    private function makeRequest(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:request NomRequest\n";
            return;
        }

        $name = str_ends_with($name, 'Request') ? $name : $name . 'Request';
        $dir = $this->basePath . '/app/Requests';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "$dir/$name.php";

        if (file_exists($path)) {
            echo "La requête $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        namespace App\Requests;

        use Niang\Core\Validation\FormRequest;

        class {$name} extends FormRequest
        {
            public function rules(): array
            {
                return [
                    // 'email' => 'required|email',
                ];
            }
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Requête créée : app/Requests/$name.php\n";
    }

    private function tinker(): void
    {
        echo "NiangPro tinker — tapez du PHP (sans balise), 'exit' pour quitter.\n";

        while (true) {
            echo '> ';
            $line = fgets(STDIN);

            if ($line === false || rtrim($line) === 'exit') {
                break;
            }

            $code = rtrim(trim($line), ';');
            if ($code === '') {
                continue;
            }

            try {
                try {
                    // la plupart des lignes tapées sont des expressions : on tente de récupérer leur valeur
                    $result = eval("return $code;");
                } catch (\ParseError) {
                    $result = eval("$code;");
                }

                if ($result !== null) {
                    var_export($result);
                    echo "\n";
                }
            } catch (\Throwable $e) {
                echo 'Erreur : ' . $e->getMessage() . "\n";
            }
        }
    }

    private function keyGenerate(): void
    {
        $key = bin2hex(random_bytes(32));
        $path = $this->basePath . '/.env';
        $env = file_exists($path) ? file_get_contents($path) : '';

        if (preg_match('/^APP_KEY=.*$/m', $env)) {
            $env = preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$key", $env);
        } else {
            $env .= (($env !== '' && !str_ends_with($env, "\n")) ? "\n" : '') . "APP_KEY=$key\n";
        }

        file_put_contents($path, $env);
        echo "Nouvelle clé générée dans .env\n";
    }

    private function queueWork(): void
    {
        $count = Queue::work();
        echo $count > 0 ? "$count job(s) traité(s).\n" : "Aucun job en attente.\n";
    }

    private function cacheClear(): void
    {
        Cache::flush();
        echo "Cache applicatif vidé.\n";
    }

    private function optimize(): void
    {
        $this->routeCache();
        echo "Pensez aussi, en production : composer install --no-dev --optimize-autoloader\n";
        echo "et activez opcache.validate_timestamps=0 dans votre php.ini.\n";
    }

    /** Clone le projet courant (sans vendor/, .git/, données locales) comme squelette d'un nouveau projet. */
    private function newProject(?string $name): void
    {
        if (!$name || !preg_match('/^[a-zA-Z0-9_-]+$/', $name)) {
            echo "Usage : niang new mon-app (lettres, chiffres, - et _ uniquement)\n";
            return;
        }

        $target = dirname($this->basePath) . "/$name";

        if (file_exists($target)) {
            echo "$target existe déjà.\n";
            return;
        }

        echo "Création du projet dans $target...\n";

        $this->copyDirectory($this->basePath, $target, [
            'vendor', '.git', '.env', '.php-cs-fixer.cache', '.phpunit.result.cache',
            'storage/logs', 'storage/framework', 'storage/database.sqlite',
        ]);

        mkdir("$target/storage/logs", 0755, true);
        mkdir("$target/storage/framework", 0755, true);
        touch("$target/storage/logs/.gitkeep");

        chmod("$target/bin/niang", 0755);
        copy("$target/.env.example", "$target/.env");

        echo "Installation des dépendances...\n";
        passthru('composer install --working-dir=' . escapeshellarg($target) . ' --quiet');

        $key = bin2hex(random_bytes(32));
        $env = file_get_contents("$target/.env");
        $env = preg_match('/^APP_KEY=.*$/m', $env)
            ? preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$key", $env)
            : $env . "APP_KEY=$key\n";
        file_put_contents("$target/.env", $env);

        echo "\nProjet créé.\n\n  cd $name\n  ./bin/niang migrate\n  ./bin/niang serve\n";
    }

    /** Installe un raccourci global `np` (macOS/Linux) qui trouve bin/niang en remontant depuis le dossier courant. */
    private function npInstall(): void
    {
        if (str_starts_with(PHP_OS_FAMILY, 'Windows')) {
            echo "np:install n'est pas encore disponible sur Windows. Créez un alias PowerShell manuellement :\n";
            echo "  Set-Alias np .\\bin\\niang\n";
            return;
        }

        $dir = $this->findWritablePathDir();

        if (!$dir) {
            echo "Aucun dossier de votre PATH n'est accessible en écriture.\n";
            echo "Créez-en un et ajoutez-le à votre PATH, par exemple :\n";
            echo "  mkdir -p ~/.local/bin && echo 'export PATH=\"\$HOME/.local/bin:\$PATH\"' >> ~/.zshrc\n";
            echo "puis relancez : ./bin/niang np:install\n";
            return;
        }

        $script = <<<'BASH'
        #!/usr/bin/env bash
        # Raccourci pour ./bin/niang : cherche bin/niang en remontant depuis le dossier courant.
        dir="$PWD"
        while [ "$dir" != "/" ]; do
            if [ -x "$dir/bin/niang" ]; then
                exec "$dir/bin/niang" "$@"
            fi
            dir="$(dirname "$dir")"
        done

        echo "np : bin/niang introuvable (es-tu dans un projet NiangPro ?)" >&2
        exit 1

        BASH;

        $path = "$dir/np";
        file_put_contents($path, $script);
        chmod($path, 0755);

        echo "Raccourci installé : $path\n";
        echo "Utilisez `np serve`, `np migrate`, etc. depuis n'importe quel projet NiangPro.\n";
    }

    /** Premier dossier du PATH existant et accessible en écriture, ou null si aucun. */
    private function findWritablePathDir(): ?string
    {
        $preferred = [getenv('HOME') . '/.local/bin', '/opt/homebrew/bin', '/usr/local/bin'];
        $pathDirs = array_filter(explode(PATH_SEPARATOR, (string) getenv('PATH')));

        foreach ([...$preferred, ...$pathDirs] as $dir) {
            if (is_dir($dir) && is_writable($dir) && in_array($dir, $pathDirs, true)) {
                return $dir;
            }
        }

        return null;
    }

    private function copyDirectory(string $source, string $target, array $exclude): void
    {
        mkdir($target, 0755, true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($source) + 1);

            foreach ($exclude as $pattern) {
                if ($relative === $pattern || str_starts_with($relative, "$pattern/")) {
                    continue 2;
                }
            }

            $destination = "$target/$relative";

            if ($item->isDir()) {
                if (!is_dir($destination)) {
                    mkdir($destination, 0755, true);
                }
            } else {
                copy($item->getPathname(), $destination);
            }
        }
    }

    private function help(): void
    {
        echo <<<TEXT
        NiangPro CLI

        Commandes disponibles :
          serve [host:port]        Démarre le serveur de développement (défaut 127.0.0.1:8000)
          make:controller <Nom>    Génère un contrôleur dans app/Controllers
          make:model <Nom>         Génère un modèle dans app/Models
          make:migration <nom>     Génère une migration dans database/migrations
          make:seeder <Nom>        Génère un seeder dans database/seeders
          migrate                  Applique les migrations en attente
          migrate:rollback         Annule le dernier lot de migrations
          migrate:fresh            Réinitialise la base et rejoue toutes les migrations
          db:seed [Nom]            Exécute DatabaseSeeder, ou le seeder indiqué
          route:cache              Compile routes/web.php dans storage/framework/routes.php
          route:clear              Supprime le cache de routes
          route:list               Liste toutes les routes déclarées
          make:middleware <Nom>    Génère un middleware dans app/Middleware
          make:request <Nom>       Génère une FormRequest dans app/Requests
          tinker                   REPL interactif sur l'application
          key:generate             Génère une nouvelle APP_KEY dans .env
          queue:work               Traite les jobs différés en attente
          cache:clear              Vide le cache applicatif
          optimize                 Cache les routes + rappels de prod (opcache, autoload)
          new <nom>                Crée un nouveau projet à partir de ce squelette
          np:install                Installe le raccourci global `np` (macOS/Linux)

        TEXT;
    }
}
