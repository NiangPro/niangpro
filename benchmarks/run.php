<?php

/**
 * Benchmarks de NiangPro (roadmap §39) : « mesurée, jamais supposée ». Chaque scénario est répété,
 * après un échauffement, et on relève p50/p95/p99, le débit et la mémoire. Chaque couche est comparée
 * à son équivalent en PHP natif, pour savoir ce que coûte réellement le framework.
 *
 *   php benchmarks/run.php                 # affiche les résultats
 *   php benchmarks/run.php --markdown      # écrit aussi benchmarks/RESULTS.md
 *   php benchmarks/run.php --iterations=5000
 *
 * Lancez-le avec la même configuration que la production (OPcache activé en CLI :
 * php -d opcache.enable_cli=1 benchmarks/run.php) et sur une machine au repos.
 */

use Niang\Core\Application;
use Niang\Core\Container;
use Niang\Core\Database\DB;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Router;
use Niang\Core\View;

require __DIR__ . '/../vendor/autoload.php';

putenv('APP_ENV=testing');
putenv('APP_DEBUG=false');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');

$options = getopt('', ['iterations:', 'markdown']);
$iterations = (int) ($options['iterations'] ?? 2000);
$markdown = array_key_exists('markdown', $options);

final class Bench
{
    /** @var list<array{group: string, name: string, p50: float, p95: float, p99: float, rps: float, memory: float}> */
    public static array $results = [];

    public static function run(string $group, string $name, int $iterations, \Closure $scenario): void
    {
        for ($i = 0; $i < min(200, $iterations); $i++) {
            $scenario(); // échauffement : autoload, OPcache, caches internes
        }

        // Tableau des mesures alloué AVANT la mesure mémoire : sinon il serait compté lui-même.
        $samples = array_fill(0, $iterations, 0.0);
        gc_collect_cycles();
        $memoryBefore = memory_get_usage();

        for ($i = 0; $i < $iterations; $i++) {
            $start = hrtime(true);
            $scenario();
            $samples[$i] = (hrtime(true) - $start) / 1e6;
        }

        gc_collect_cycles();
        $retained = max(0, memory_get_usage() - $memoryBefore) / 1024;

        sort($samples);
        $total = array_sum($samples);
        $at = fn (float $p) => $samples[min(count($samples) - 1, (int) floor($p * count($samples)))];

        self::$results[] = [
            'group' => $group,
            'name' => $name,
            'p50' => $at(0.50),
            'p95' => $at(0.95),
            'p99' => $at(0.99),
            'rps' => $total > 0 ? $iterations / ($total / 1000) : 0,
            'memory' => $retained,
        ];
    }
}

$app = new Application(dirname(__DIR__));
$container = new Container();

// --- Routage : 200 routes, la dernière demandée (pire cas pour une recherche linéaire)
$router = new Router();
for ($i = 0; $i < 200; $i++) {
    $router->get("/ressource$i/{id}", fn (string $id) => Response::html($id));
}
$nativeRoutes = [];
for ($i = 0; $i < 200; $i++) {
    $nativeRoutes["#^/ressource$i/(?P<id>[^/]+)$#"] = fn (string $id) => $id;
}

Bench::run('Routage (200 routes)', 'PHP natif (boucle naïve de preg_match)', $iterations, function () use ($nativeRoutes) {
    foreach ($nativeRoutes as $pattern => $handler) {
        if (preg_match($pattern, '/ressource199/42', $m)) {
            return $handler($m['id']);
        }
    }
});
Bench::run('Routage (200 routes)', 'NiangPro Router::dispatch()', $iterations, function () use ($router, $container) {
    $router->dispatch(Request::create('GET', '/ressource199/42'), $container);
});

// --- Conteneur : un graphe de 3 dépendances résolu par auto-wiring
final class BenchLogger
{
}
final class BenchRepository
{
    public function __construct(public BenchLogger $logger)
    {
    }
}
final class BenchService
{
    public function __construct(public BenchRepository $repository, public BenchLogger $logger)
    {
    }
}

Bench::run('Conteneur', 'PHP natif (new)', $iterations, fn () => new BenchService(new BenchRepository(new BenchLogger()), new BenchLogger()));
Bench::run('Conteneur', 'NiangPro Container::make() (auto-wiring)', $iterations, fn () => $container->make(BenchService::class));

// --- Requête / réponse
Bench::run('Requête et réponse', 'PHP natif (json_encode)', $iterations, fn () => json_encode(['id' => 1, 'nom' => 'Awa']));
Bench::run('Requête et réponse', 'NiangPro Request::create() + Response::json()', $iterations, function () {
    Request::create('POST', '/api/x', ['nom' => 'Awa']);
    Response::json(['id' => 1, 'nom' => 'Awa'])->getContent();
});

// --- Base de données (SQLite en mémoire)
Schema::create('bench_items', function ($table) {
    $table->id();
    $table->string('name');
    $table->integer('views');
});
for ($i = 0; $i < 500; $i++) {
    (new QueryBuilder('bench_items'))->insert(['name' => "n$i", 'views' => $i]);
}
$pdo = DB::connection();
$statement = $pdo->prepare('SELECT * FROM bench_items WHERE views > ? ORDER BY id LIMIT 20');

Bench::run('Base de données (SQLite)', 'PDO natif (requête préparée)', $iterations, function () use ($statement) {
    $statement->execute([100]);
    $statement->fetchAll(PDO::FETCH_ASSOC);
});
Bench::run('Base de données (SQLite)', 'NiangPro QueryBuilder', $iterations, function () {
    (new QueryBuilder('bench_items'))->where('views', '>', 100)->orderBy('id')->limit(20)->get();
});

// --- Rendu d'une vue
Bench::run('Rendu', 'PHP natif (include + ob_start)', $iterations, function () {
    ob_start();
    $title = 'Accueil';
    echo '<h1>' . htmlspecialchars($title) . '</h1>';
    ob_get_clean();
});
Bench::run('Rendu', 'NiangPro View::make() (page d\'erreur 404)', $iterations, fn () => View::make('errors.404')->getContent());

// --- Requête complète : routage, middlewares, en-têtes de sécurité, réponse JSON
$app->router->get('/bench', fn () => Response::json(['ok' => true]));
Bench::run('Requête complète', 'NiangPro Application::handle() (GET JSON)', $iterations, function () use ($app) {
    $app->handle(Request::create('GET', '/bench'));
});

// --- Démarrage : nouvelle Application (config, conteneur, providers)
Bench::run('Démarrage', 'new Application() (config, conteneur, providers)', max(100, intdiv($iterations, 10)), fn () => new Application(dirname(__DIR__)));

// --- Rapport
$conditions = [
    'PHP' => PHP_VERSION,
    'OPcache (CLI)' => ini_get('opcache.enable_cli') ? 'activé' : 'désactivé',
    'JIT' => (string) (ini_get('opcache.jit') ?: 'désactivé'),
    'Processeur' => trim((string) (PHP_OS_FAMILY === 'Darwin' ? shell_exec('sysctl -n machdep.cpu.brand_string') : shell_exec("grep -m1 'model name' /proc/cpuinfo | cut -d: -f2"))) ?: 'inconnu',
    'Mémoire' => PHP_OS_FAMILY === 'Darwin' ? round((int) shell_exec('sysctl -n hw.memsize') / 1024 ** 3) . ' Go' : 'voir /proc/meminfo',
    'Système' => PHP_OS_FAMILY . ' ' . php_uname('r'),
    'Base' => 'SQLite en mémoire, 500 lignes',
    'Itérations' => (string) $iterations . ' par scénario, après échauffement',
    'Date' => date('Y-m-d'),
];

$lines = ['| Scénario | Mesure | p50 (ms) | p95 (ms) | p99 (ms) | Opérations/s | Mémoire retenue après la série (Ko) |', '| --- | --- | ---: | ---: | ---: | ---: | ---: |'];
foreach (Bench::$results as $r) {
    $lines[] = sprintf('| %s | %s | %.4f | %.4f | %.4f | %s | %.1f |', $r['group'], $r['name'], $r['p50'], $r['p95'], $r['p99'], number_format($r['rps'], 0, ',', ' '), $r['memory']);
}

echo "Conditions\n";
foreach ($conditions as $label => $value) {
    echo "  $label : $value\n";
}
echo "\n" . implode("\n", $lines) . "\n";

if ($markdown) {
    $conditionLines = array_map(fn ($label, $value) => "- **$label** : $value", array_keys($conditions), $conditions);
    file_put_contents(__DIR__ . '/RESULTS.md', "# Résultats des benchmarks\n\nGénéré par `php benchmarks/run.php --markdown`. Ce sont des micro-benchmarks : ils mesurent le coût\n"
        . "de chaque couche du framework face à son équivalent en PHP natif, pas les performances d'une application\n"
        . "réelle (qui dépendent surtout de ses requêtes SQL et de ses appels externes).\n\n## Conditions\n\n"
        . implode("\n", $conditionLines) . "\n\n## Mesures\n\n" . implode("\n", $lines) . "\n");
    echo "\nÉcrit dans benchmarks/RESULTS.md\n";
}
