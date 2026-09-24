<?php

namespace Niang\Core\Console;

use Niang\Core\Cache;
use Niang\Core\ConfigCache;
use Niang\Core\Database\DB;
use Niang\Core\Database\Migrator;
use Niang\Core\Database\Seeder;
use Niang\Core\Env;
use Niang\Core\HealthCheck;
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
            'queue:failed' => $this->queueFailed(),
            'queue:retry' => $this->queueRetry($arg),
            'queue:flush' => $this->queueFlush(),
            'cache:clear' => $this->cacheClear(),
            'optimize' => $this->optimize(),
            'new' => $this->newProject(array_slice($argv, 2)),
            'np:install' => $this->npInstall(),
            'config:cache' => $this->configCache(),
            'config:clear' => $this->configClear(),
            'doctor' => $this->doctor(),
            'health' => $this->health(),
            'make:policy' => $this->makePolicy($arg),
            'make:job' => $this->makeJob($arg),
            'make:event' => $this->makeEvent($arg),
            'make:command' => $this->makeCommand($arg),
            'make:test' => $this->makeTest($arg),
            'theme:add' => $this->themeAdd($arg),
            default => $this->runCustomCommand($command, array_slice($argv, 2)) ? null : $this->help(),
        };
    }

    /**
     * Cherche, parmi app/Console/Commands/*.php, une classe dont Command::$signature correspond
     * au nom tapé, et l'exécute. Retourne false (plutôt que d'afficher une erreur) si rien ne
     * correspond, pour laisser l'appelant retomber sur l'aide générale.
     */
    private function runCustomCommand(string $command, array $arguments): bool
    {
        $dir = $this->basePath . '/app/Console/Commands';

        if (!is_dir($dir)) {
            return false;
        }

        foreach (glob("$dir/*.php") ?: [] as $file) {
            require_once $file;
            $class = 'App\\Console\\Commands\\' . basename($file, '.php');

            if (is_subclass_of($class, Command::class) && $class::$signature === $command) {
                (new $class())->handle($arguments);
                return true;
            }
        }

        return false;
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
        $cached = RouteCache::store($router->routes(), Router::namedRoutes(), $router->fallbackAction());
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

        if (($fallback = $router->fallbackAction()) !== null) {
            $action = match (true) {
                $fallback instanceof \Closure => '{closure}',
                is_array($fallback) => $fallback[0] . '@' . $fallback[1],
                default => (string) $fallback,
            };
            printf("%-7s %-30s %-20s %s\n", 'ANY', '{fallback}', '', $action);
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

            // public function messages(): array
            // {
            //     return ['email.required' => 'Merci de renseigner votre email.'];
            // }

            // public function attributes(): array
            // {
            //     return ['email' => 'Adresse email'];
            // }
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Requête créée : app/Requests/$name.php\n";
    }

    private function makePolicy(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:policy NomPolicy\n";
            return;
        }

        $name = str_ends_with($name, 'Policy') ? $name : $name . 'Policy';
        $dir = $this->basePath . '/app/Policies';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "$dir/$name.php";

        if (file_exists($path)) {
            echo "La policy $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        namespace App\Policies;

        class {$name}
        {
            // Chaque méthode reçoit l'utilisateur connecté (ou null) et l'enregistrement concerné,
            // et doit renvoyer un booléen. Enregistrez la policy dans routes/web.php :
            //   Gate::policy('prefix', {$name}::class);
            // 'prefix.update' résoudra alors vers update() ci-dessous.

            // public function update(?array \$user, array \$model): bool
            // {
            //     return \$user !== null;
            // }

            // public function delete(?array \$user, array \$model): bool
            // {
            //     return \$user !== null;
            // }
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Policy créée : app/Policies/$name.php\n";
    }

    private function makeJob(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:job NomJob\n";
            return;
        }

        $name = str_ends_with($name, 'Job') ? $name : $name . 'Job';
        $dir = $this->basePath . '/app/Jobs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "$dir/$name.php";

        if (file_exists($path)) {
            echo "Le job $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        namespace App\Jobs;

        use Niang\Core\Job;

        class {$name} extends Job
        {
            public function __construct()
            {
            }

            public function handle(): void
            {
                //
            }
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Job créé : app/Jobs/$name.php\n";
    }

    private function makeEvent(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:event NomEvent\n";
            return;
        }

        $name = str_ends_with($name, 'Event') ? $name : $name . 'Event';
        $dir = $this->basePath . '/app/Events';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "$dir/$name.php";

        if (file_exists($path)) {
            echo "L'événement $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        namespace App\Events;

        /**
         * Event::listen({$name}::class, function ({$name} \$event): void {
         *     //
         * });
         *
         * Event::dispatch({$name}::class, new {$name}(...));
         */
        class {$name}
        {
            public function __construct(
                // public readonly array \$user,
            ) {
            }
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Événement créé : app/Events/$name.php\n";
    }

    private function makeCommand(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:command NomCommand\n";
            return;
        }

        $name = str_ends_with($name, 'Command') ? $name : $name . 'Command';
        $dir = $this->basePath . '/app/Console/Commands';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "$dir/$name.php";

        if (file_exists($path)) {
            echo "La commande $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        namespace App\Console\Commands;

        use Niang\Core\Console\Command;

        class {$name} extends Command
        {
            /** Nom invoqué en CLI : niang mon:nom */
            public static string \$signature = 'mon:nom';

            public static string \$description = '';

            public function handle(array \$arguments): void
            {
                //
            }
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Commande créée : app/Console/Commands/$name.php\n";
        echo "Pensez à changer \$signature avant de lancer `niang mon:nom`.\n";
    }

    private function makeTest(?string $name): void
    {
        if (!$name) {
            echo "Usage : niang make:test NomTest\n";
            return;
        }

        $name = str_ends_with($name, 'Test') ? $name : $name . 'Test';
        $dir = $this->basePath . '/tests/Unit';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "$dir/$name.php";

        if (file_exists($path)) {
            echo "Le test $name existe déjà.\n";
            return;
        }

        $stub = <<<PHP
        <?php

        namespace Tests\Unit;

        use PHPUnit\Framework\TestCase;

        class {$name} extends TestCase
        {
            public function test_example(): void
            {
                \$this->assertTrue(true);
            }
        }

        PHP;

        file_put_contents($path, $stub);
        echo "Test créé : tests/Unit/$name.php\n";
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

    private function queueFailed(): void
    {
        $failed = Queue::failed();

        if (!$failed) {
            echo "Aucun job échoué.\n";
            return;
        }

        foreach ($failed as $job) {
            echo "{$job['id']}  {$job['class']}  (file: {$job['queue']}, échoué le {$job['failed_at']})\n";
            echo "  {$job['error']}\n";
        }
    }

    private function queueRetry(?string $id): void
    {
        if (!$id) {
            echo "Usage : niang queue:retry <id>\n";
            return;
        }

        echo Queue::retry($id) ? "Job $id remis en file.\n" : "Aucun job échoué avec l'id $id.\n";
    }

    private function queueFlush(): void
    {
        $count = Queue::flush();
        echo "$count job(s) échoué(s) supprimé(s).\n";
    }

    private function cacheClear(): void
    {
        Cache::flush();
        echo "Cache applicatif vidé.\n";
    }

    private function optimize(): void
    {
        $this->routeCache();
        $this->configCache();
        echo "Pensez aussi, en production : composer install --no-dev --optimize-autoloader\n";
        echo "et activez opcache.validate_timestamps=0 dans votre php.ini.\n";
        echo "preload.php existe à la racine du projet : un réglage serveur (opcache.preload dans\n";
        echo "php.ini), pas quelque chose qu'une commande CLI ponctuelle comme celle-ci peut activer.\n";
        echo "Voir docs/ROADMAP_TECHNIQUE.md, section OPcache / production.\n";
    }

    /**
     * Fige config/*.php (et les env() qu'ils contiennent) dans un seul fichier. Production
     * uniquement : tant que le cache existe, modifier .env ou config/*.php n'a plus d'effet.
     */
    private function configCache(): void
    {
        $items = [];

        foreach (glob($this->basePath . '/config/*.php') ?: [] as $file) {
            $items[basename($file, '.php')] = require $file;
        }

        ConfigCache::store($items);
        echo "Configuration mise en cache : storage/framework/config.php\n";
    }

    private function configClear(): void
    {
        ConfigCache::clear();
        echo "Cache de configuration supprimé.\n";
    }

    /**
     * Code de sortie non nul si au moins une vérification critique échoue, pour un usage en
     * script de déploiement (`niang doctor || exit 1`).
     */
    private function doctor(): void
    {
        $results = $this->doctorChecks();

        $failures = 0;
        foreach ($results as [$status, $message]) {
            $icon = match ($status) {
                'fail' => '✗',
                'warn' => '⚠',
                default => '✓',
            };
            echo "$icon $message\n";
            $failures += $status === 'fail' ? 1 : 0;
        }

        echo "\n" . ($failures === 0 ? "Tout est en ordre.\n" : "$failures vérification(s) en échec.\n");

        if ($failures > 0) {
            exit(1);
        }
    }

    /**
     * Chaque ligne est indépendante (une extension manquante n'empêche pas de vérifier le reste)
     * pour donner d'un coup toute la liste à corriger plutôt qu'une erreur à la fois. Séparée de
     * doctor() (qui affiche et appelle exit()) pour rester testable en process.
     *
     * @return list<array{0: 'ok'|'warn'|'fail', 1: string}>
     */
    private function doctorChecks(): array
    {
        $driver = Env::get('DB_CONNECTION', 'sqlite');
        $driverExtension = match ($driver) {
            'mysql' => 'pdo_mysql',
            'pgsql' => 'pdo_pgsql',
            default => 'pdo_sqlite',
        };

        $results = [
            $this->doctorCheck(
                version_compare(PHP_VERSION, '8.1.0', '>='),
                'PHP ' . PHP_VERSION,
                'PHP 8.1.0 minimum requis, ' . PHP_VERSION . ' détecté'
            ),
            $this->doctorCheck(extension_loaded('pdo'), 'Extension pdo', 'Extension pdo manquante'),
            $this->doctorCheck(
                extension_loaded($driverExtension),
                "Extension $driverExtension",
                "Extension $driverExtension manquante (DB_CONNECTION=$driver)"
            ),
            $this->doctorCheck(extension_loaded('json'), 'Extension json', 'Extension json manquante'),
            $this->doctorCheck(
                file_exists($this->basePath . '/.env'),
                '.env présent',
                '.env introuvable — copiez .env.example vers .env'
            ),
            $this->doctorCheck(
                Env::get('APP_KEY', '') !== '',
                'APP_KEY configurée',
                'APP_KEY vide — lancez `niang key:generate`'
            ),
        ];

        foreach (['storage', 'storage/logs', 'storage/framework'] as $dir) {
            $path = $this->basePath . '/' . $dir;
            $writable = is_dir($path) ? is_writable($path) : @mkdir($path, 0755, true);
            $results[] = $this->doctorCheck(
                $writable,
                "$dir accessible en écriture",
                "$dir n'existe pas ou n'est pas accessible en écriture"
            );
        }

        try {
            DB::connection()->query('SELECT 1');
            $results[] = $this->doctorCheck(true, "Connexion base de données ($driver)", '');
        } catch (\Throwable $e) {
            $results[] = $this->doctorCheck(false, '', "Connexion base de données ($driver) : " . $e->getMessage());
        }

        try {
            $router = new Router();
            require $this->basePath . '/routes/web.php';
            $results[] = $this->doctorCheck(true, count($router->routes()) . ' route(s) déclarée(s)', '');
        } catch (\Throwable $e) {
            $results[] = $this->doctorCheck(false, '', 'Chargement de routes/web.php : ' . $e->getMessage());
        }

        if (Env::get('APP_ENV') === 'production' && Env::get('APP_DEBUG', 'true') === 'true') {
            $results[] = ['warn', 'APP_DEBUG=true en production — désactivez-le avant déploiement'];
        }

        array_push($results, ...$this->mailChecks());
        return $results;
    }

    /**
     * Configuration mail, sans se connecter au serveur (doctor vérifie l'environnement statique ;
     * un vrai envoi reste le seul test complet).
     *
     * @return list<array{0: 'ok'|'fail'|'warn', 1: string}>
     */
    private function mailChecks(): array
    {
        $mailer = (string) Env::get('MAIL_MAILER', 'log');

        if ($mailer === 'smtp') {
            $missing = array_values(array_filter(['MAIL_HOST', 'MAIL_FROM_ADDRESS'], fn (string $key) => (string) Env::get($key, '') === ''));
            $encryption = strtolower((string) Env::get('MAIL_ENCRYPTION', 'tls'));

            return [
                $this->doctorCheck(
                    $missing === [],
                    'Mail SMTP configuré (' . Env::get('MAIL_HOST') . ')',
                    'MAIL_MAILER=smtp mais ' . implode(' et ', $missing) . ' vide(s) dans .env'
                ),
                $this->doctorCheck(
                    $encryption === 'none' || extension_loaded('openssl'),
                    "Chiffrement SMTP : $encryption",
                    "Extension openssl manquante (MAIL_ENCRYPTION=$encryption)"
                ),
            ];
        }

        if (!in_array($mailer, ['log', 'array'], true)) {
            return [$this->doctorCheck(false, '', "MAIL_MAILER inconnu : « $mailer » (attendu : smtp, log ou array)")];
        }

        return Env::get('APP_ENV') === 'production'
            ? [['warn', "MAIL_MAILER=$mailer en production — aucun email ne sera réellement envoyé (utilisez smtp)"]]
            : [];
    }

    /** @return array{0: 'ok'|'fail', 1: string} */
    private function doctorCheck(bool $ok, string $okMessage, string $failMessage): array
    {
        return $ok ? ['ok', $okMessage] : ['fail', $failMessage];
    }

    /** Même logique que GET /health|/up (Niang\Core\HealthCheck) — utile en pré-déploiement sans faire de requête HTTP. */
    private function health(): void
    {
        $result = HealthCheck::run();

        foreach ($result['services'] as $service => $state) {
            echo ($state === 'ok' ? '✓' : '✗') . " $service : $state\n";
        }

        echo "\nStatut global : {$result['status']}\n";

        if ($result['status'] !== 'ok') {
            exit(1);
        }
    }

    /**
     * Clone le projet courant (sans vendor/, .git/, données locales) comme squelette d'un nouveau
     * projet, puis y installe le thème du type de site choisi (--type=<slug>, NIANG_SITE_TYPE, ou
     * une question posée sur un terminal ; « minimal » sinon) — même logique que le hook Composer
     * de `composer create-project` (voir Niang\Core\Console\ComposerHooks).
     *
     * @param list<string> $arguments ce qui suit `new` sur la ligne de commande
     */
    private function newProject(array $arguments): void
    {
        [$name, $options] = $this->parseNewArguments($arguments);

        if (!$name || !preg_match('/^[a-zA-Z0-9_-]+$/', $name) || array_diff(array_keys($options), ['type'])) {
            echo "Usage : niang new mon-app [--type=<slug>] (lettres, chiffres, - et _ uniquement)\n";
            echo 'Types disponibles : ' . implode(', ', array_keys($this->scaffolder()->catalog())) . "\n";
            return;
        }

        $target = dirname($this->basePath) . "/$name";

        if (file_exists($target)) {
            echo "$target existe déjà.\n";
            return;
        }

        // Posée avant toute copie : l'utilisateur répond tout de suite, plutôt que d'être interrompu
        // après l'attente de `composer install`, et un --type invalide ne laisse rien derrière lui.
        $type = $this->resolveSiteType($options['type'] ?? null);

        if ($type === null) {
            exit(1);
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

        // Un thème décrit lui-même ses étapes (theme.json) : une boutique doit migrer et alimenter la
        // base, un site vitrine n'en a pas besoin. Le squelette minimal garde ses étapes historiques.
        $nextSteps = $this->installSiteTheme($target, $type) ?: ['./bin/niang migrate', './bin/niang serve'];

        echo "\nProjet créé.\n\n  cd $name\n";

        foreach ($nextSteps as $step) {
            echo "  $step\n";
        }

        $notes = $this->scaffolder()->notes($type);

        if ($notes) {
            echo "\n";

            foreach ($notes as $note) {
                echo "$note\n";
            }
        }
    }

    /**
     * Arguments de `niang new` : un nom (le premier argument sans tiret) et des options --clé=valeur,
     * dans n'importe quel ordre (`niang new --type=blog mon-app` fonctionne comme `niang new mon-app --type=blog`).
     *
     * @param list<string> $arguments
     * @return array{0: ?string, 1: array<string, ?string>}
     */
    private function parseNewArguments(array $arguments): array
    {
        $name = null;
        $options = [];

        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '--')) {
                [$key, $value] = array_pad(explode('=', substr($argument, 2), 2), 2, null);
                $options[$key] = $value;
                continue;
            }

            $name ??= $argument;
        }

        return [$name, $options];
    }

    /**
     * Demande (ou déduit) le type de site. Retourne null, après avoir affiché pourquoi, si le type
     * explicitement demandé n'existe pas — jamais de repli silencieux sur « minimal » dans ce cas.
     */
    private function resolveSiteType(?string $requested): ?string
    {
        try {
            return (new SiteTypePrompt($this->scaffolder()))->resolve(
                $requested,
                SiteTypePrompt::stdinIsInteractive(),
                function (string $question): ?string {
                    echo $question;
                    $line = fgets(STDIN);

                    return $line === false ? null : rtrim($line, "\r\n");
                },
                static function (string $text): void {
                    echo $text;
                }
            );
        } catch (\InvalidArgumentException $e) {
            echo $e->getMessage() . "\n";

            return null;
        }
    }

    /**
     * Installe le thème dans le projet $target, exécute ses commandes de setup (base de données,
     * compte administrateur de test...) et retourne les commandes qu'il reste à suggérer.
     *
     * @return list<string>
     */
    private function installSiteTheme(string $target, string $type): array
    {
        $scaffolder = $this->scaffolder();
        $scaffolder->install($type, $target);

        if ($type === ProjectScaffolder::DEFAULT_TYPE) {
            return [];
        }

        echo "Thème « {$scaffolder->catalog()[$type]} » installé.\n";

        $setup = $scaffolder->setup($type);
        $done = [];

        if ($setup) {
            echo 'Préparation du projet (' . implode(', ', $setup) . ")...\n";
            $done = (new ThemeSetup($target))->run($setup, static function (string $text): void {
                echo $text;
            });
        }

        return $scaffolder->remainingSteps($type, $done);
    }

    /** Les thèmes sont lus dans le projet courant : ils sont copiés avec le squelette, et extensibles sur place. */
    private function scaffolder(): ProjectScaffolder
    {
        return new ProjectScaffolder($this->basePath . '/resources/scaffold');
    }

    /**
     * Installe un thème publié comme paquet Composer tiers : `composer require --dev` (le paquet
     * n'a besoin d'exister qu'au moment de créer des projets, jamais en production) puis copie son
     * dossier de thème (extra.niangpro-theme de son composer.json) dans resources/scaffold/themes/
     * — ProjectScaffolder n'a besoin d'aucune modification pour le proposer ensuite : un thème est
     * déjà « juste un dossier ». Voir Niang\Core\Console\ThemePackageInstaller pour la logique pure.
     */
    private function themeAdd(?string $package): void
    {
        if (!$package) {
            echo "Usage : niang theme:add vendor/paquet\n";
            return;
        }

        echo "composer require --dev $package\n";
        passthru('composer require --dev ' . escapeshellarg($package) . ' --working-dir=' . escapeshellarg($this->basePath), $exitCode);

        if ($exitCode !== 0) {
            echo "Échec de composer require --dev $package (code $exitCode).\n";
            exit(1);
        }

        try {
            $slug = (new ThemePackageInstaller($this->basePath))->install($package);
        } catch (\InvalidArgumentException $e) {
            echo 'Erreur : ' . $e->getMessage() . "\n";
            exit(1);
        }

        echo "Thème « $slug » installé dans resources/scaffold/themes/$slug\n";
        echo "Visible dans `niang new --type=$slug` et `NIANG_SITE_TYPE=$slug composer create-project ...`.\n";
    }

    /**
     * Installe un raccourci global `np` qui trouve bin/niang en remontant depuis le dossier courant :
     * script bash sur macOS/Linux, np.cmd sur Windows (utilisable depuis cmd comme depuis PowerShell).
     */
    private function npInstall(): void
    {
        $windows = PHP_OS_FAMILY === 'Windows';
        $fallback = $this->npFallbackDir($windows);
        $dir = $this->findWritablePathDir($windows, $fallback);
        $inPath = $dir !== null;

        if (!$inPath) {
            // Aucun dossier du PATH n'est accessible en écriture : on installe dans un dossier
            // personnel, et on explique comment l'ajouter au PATH.
            if ($fallback === null) {
                echo "Impossible de déterminer votre dossier personnel (" . ($windows ? 'LOCALAPPDATA' : 'HOME') . " non défini).\n";
                exit(1);
            }

            if (!is_dir($fallback) && !@mkdir($fallback, 0755, true)) {
                echo "Impossible de créer le dossier $fallback.\n";
                exit(1);
            }

            $dir = $fallback;
        }

        $path = $dir . DIRECTORY_SEPARATOR . ($windows ? 'np.cmd' : 'np');

        if (@file_put_contents($path, $windows ? $this->npWindowsScript() : $this->npUnixScript()) === false) {
            echo "Impossible d'écrire $path.\n";
            exit(1);
        }

        if (!$windows) {
            chmod($path, 0755);
        }

        echo "Raccourci installé : $path\n";

        if (!$inPath) {
            echo "\nCe dossier n'est pas encore dans votre PATH. Ajoutez-le une fois pour toutes :\n";

            if ($windows) {
                echo "  (PowerShell) [Environment]::SetEnvironmentVariable('Path', [Environment]::GetEnvironmentVariable('Path', 'User') + ';$dir', 'User')\n";
            } else {
                $rc = match (basename((string) getenv('SHELL'))) {
                    'zsh' => '~/.zshrc',
                    'bash' => PHP_OS_FAMILY === 'Darwin' ? '~/.bash_profile' : '~/.bashrc',
                    default => '~/.profile',
                };
                echo "  echo 'export PATH=\"\$HOME/.local/bin:\$PATH\"' >> $rc\n";
            }

            echo "puis ouvrez un nouveau terminal.\n\n";
        }

        echo "Utilisez `np serve`, `np migrate`, etc. depuis n'importe quel projet NiangPro.\n";
    }

    /** Dossier personnel où poser `np` quand aucun dossier du PATH n'est accessible en écriture. */
    private function npFallbackDir(bool $windows): ?string
    {
        $base = getenv($windows ? 'LOCALAPPDATA' : 'HOME');

        if (!$base) {
            return null;
        }

        return $windows ? "$base\\NiangPro\\bin" : "$base/.local/bin";
    }

    /**
     * Premier dossier du PATH existant et accessible en écriture, ou null si aucun.
     * Sur Windows, on se limite à des dossiers personnels connus : un terminal administrateur peut
     * écrire dans C:\Windows\System32, qui est dans le PATH, et ce n'est pas l'endroit où poser `np`.
     */
    private function findWritablePathDir(bool $windows, ?string $fallback): ?string
    {
        $normalize = static fn (string $dir): string => $windows
            ? strtolower(rtrim(str_replace('/', '\\', $dir), '\\'))
            : rtrim($dir, '/');

        $pathDirs = array_map($normalize, array_filter(explode(PATH_SEPARATOR, (string) getenv('PATH'))));

        if ($windows) {
            $appData = getenv('APPDATA');
            $candidates = array_filter([$fallback, $appData ? "$appData\\Composer\\vendor\\bin" : null]);
        } else {
            $candidates = [...array_filter([$fallback]), '/opt/homebrew/bin', '/usr/local/bin', ...explode(PATH_SEPARATOR, (string) getenv('PATH'))];
        }

        foreach ($candidates as $dir) {
            if ($dir !== '' && is_dir($dir) && is_writable($dir) && in_array($normalize($dir), $pathDirs, true)) {
                return $dir;
            }
        }

        return null;
    }

    private function npUnixScript(): string
    {
        return <<<'BASH'
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
    }

    /**
     * Équivalent Windows (batch) : cmd ne sait pas exécuter bin/niang directement (pas de shebang),
     * d'où l'appel explicite à php. Fins de ligne CRLF : avec LF seul, cmd rate parfois les étiquettes
     * visées par goto. Pas d'accents : cmd n'affiche pas l'UTF-8 par défaut.
     */
    private function npWindowsScript(): string
    {
        return implode("\r\n", [
            '@echo off',
            'rem Raccourci pour bin\niang : cherche bin\niang en remontant depuis le dossier courant.',
            'setlocal',
            'set "dir=%CD%"',
            ':search',
            'if exist "%dir%\bin\niang" goto run',
            'for %%I in ("%dir%\..") do set "parent=%%~fI"',
            'if /i "%parent%"=="%dir%" goto missing',
            'set "dir=%parent%"',
            'goto search',
            ':run',
            'php "%dir%\bin\niang" %*',
            'exit /b %ERRORLEVEL%',
            ':missing',
            'echo np : bin\niang introuvable (etes-vous dans un projet NiangPro ?) 1>&2',
            'exit /b 1',
            '',
        ]);
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
          queue:failed             Liste les jobs qui ont épuisé leurs tentatives
          queue:retry <id>         Remet un job échoué en file, tentatives réinitialisées
          queue:flush              Supprime définitivement tous les jobs échoués
          cache:clear              Vide le cache applicatif
          optimize                 Cache les routes + rappels de prod (opcache, autoload)
          new <nom>                Crée un nouveau projet et y installe un thème de site (--type=<slug> pour éviter la question)
          np:install               Installe le raccourci global `np` (macOS, Linux, Windows)
          config:cache             Fige config/*.php (production uniquement)
          config:clear             Supprime le cache de configuration
          doctor                   Diagnostique l'environnement (PHP, extensions, .env, DB, storage...)
          health                   Vérifie l'état d'exécution (DB, cache, storage, queue) — même logique que GET /health
          make:policy <Nom>        Génère une policy dans app/Policies
          make:job <Nom>           Génère un job dans app/Jobs
          make:event <Nom>         Génère un événement dans app/Events
          make:command <Nom>       Génère une commande custom dans app/Console/Commands
          make:test <Nom>          Génère un test dans tests/Unit
          theme:add <vendor/paquet> Installe un thème publié comme paquet Composer (extra.niangpro-theme)

        TEXT;
    }
}
