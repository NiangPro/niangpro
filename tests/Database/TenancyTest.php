<?php

namespace Tests\Database;

use App\Middleware\IdentifyTenant;
use Niang\Core\Cache;
use Niang\Core\Config;
use Niang\Core\Database\Model;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Exceptions\TenancyException;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Job;
use Niang\Core\Log;
use Niang\Core\Queue;
use Niang\Core\Tenancy;
use Niang\Core\Testing\TestCase;
use Niang\Core\Testing\TestResponse;

/** Multi-locataire en base partagée, exécuté pour de vrai sur SQLite, MySQL et PostgreSQL. */
class TenancyTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $dakar;
    /** @var array<string, mixed> */
    private array $thies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dropTables();

        Schema::create('np_tenants', function ($table) {
            $table->id();
            $table->string('slug');
            $table->string('domain')->nullable();
        });
        Schema::create('np_tenant_projects', function ($table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('np_tenant_tags', function ($table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('label');
        });
        Schema::create('np_tenant_project_tag', function ($table) {
            $table->foreignId('project_id');
            $table->foreignId('tag_id');
        });

        Config::set('tenancy.enabled', true);
        Config::set('tenancy.table', 'np_tenants');
        Config::set('cache.driver', 'array');
        Cache::flush();

        $tenants = new QueryBuilder('np_tenants');
        $tenants->insert(['slug' => 'dakar', 'domain' => 'dakar-shop.sn']);
        $tenants->insert(['slug' => 'thies', 'domain' => 'thies-shop.sn']);
        $this->dakar = (array) Tenancy::find('slug', 'dakar');
        $this->thies = (array) Tenancy::find('slug', 'thies');
    }

    protected function tearDown(): void
    {
        Tenancy::reset();
        $this->dropTables();
        Config::load(base_path());
        parent::tearDown();
    }

    private function dropTables(): void
    {
        foreach (['np_tenant_project_tag', 'np_tenant_tags', 'np_tenant_projects', 'np_tenants'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function test_each_tenant_only_sees_and_changes_its_own_rows(): void
    {
        $dakarId = Tenancy::run($this->dakar, fn () => TenantProject::create(['name' => 'Boutique Dakar']));
        $thiesId = Tenancy::run($this->thies, fn () => TenantProject::create(['name' => 'Boutique Thiès']));

        Tenancy::run($this->dakar, function () use ($dakarId, $thiesId) {
            $this->assertSame(['Boutique Dakar'], array_column(TenantProject::all(), 'name'));
            $this->assertNull(TenantProject::find($thiesId));
            $this->assertSame(1, TenantProject::query()->count());
            $this->assertSame(1, TenantProject::paginate(10)->total);

            // Écrire chez l'autre locataire par son id : sans effet (vérifié ci-dessous, hors locataire).
            TenantProject::update($thiesId, ['name' => 'piraté']);
            TenantProject::destroy($thiesId);
            TenantProject::forceDestroy($thiesId);
            $this->assertNotNull(TenantProject::find($dakarId));
        });

        $all = Tenancy::central(fn () => TenantProject::query()->orderBy('id')->get());
        $this->assertSame(['Boutique Dakar', 'Boutique Thiès'], array_column($all, 'name'));
        $this->assertNull($all[1]['deleted_at']);
    }

    public function test_the_tenant_column_is_imposed_and_never_moved(): void
    {
        $id = Tenancy::run($this->dakar, fn () => TenantProject::forceCreate(['name' => 'A', 'tenant_id' => $this->thies['id']]));

        Tenancy::run($this->dakar, fn () => TenantProject::forceUpdate($id, ['tenant_id' => $this->thies['id'], 'name' => 'B']));

        $row = Tenancy::central(fn () => TenantProject::find($id));
        $this->assertSame((int) $this->dakar['id'], (int) $row['tenant_id']);
        $this->assertSame('B', $row['name']);
    }

    public function test_a_tenant_model_without_a_current_tenant_is_refused(): void
    {
        $this->expectException(TenancyException::class);
        $this->expectExceptionMessage('IdentifyTenant');

        TenantProject::all();
    }

    public function test_disabled_tenancy_leaves_models_unscoped(): void
    {
        Config::set('tenancy.enabled', false);

        TenantProject::forceCreate(['name' => 'Sans locataire', 'tenant_id' => 1]);

        $this->assertCount(1, TenantProject::all());
    }

    public function test_relations_written_in_sql_are_scoped_too(): void
    {
        $projectId = Tenancy::run($this->dakar, fn () => TenantProject::create(['name' => 'P']));
        $ownTag = Tenancy::run($this->dakar, fn () => TenantTag::create(['label' => 'dakar']));
        $foreignTag = Tenancy::run($this->thies, fn () => TenantTag::create(['label' => 'thies']));

        // Pivot corrompu (ou écrit à la main) qui pointe vers une étiquette d'un autre locataire.
        foreach ([$ownTag, $foreignTag] as $tagId) {
            (new QueryBuilder('np_tenant_project_tag'))->insert(['project_id' => $projectId, 'tag_id' => $tagId]);
        }

        Tenancy::run($this->dakar, function () use ($projectId) {
            $this->assertSame(['dakar'], array_column(TenantProject::tags($projectId), 'label'));
            $loaded = TenantProject::with('tags')->get();
            $this->assertSame(['dakar'], array_column($loaded[0]['tags'], 'label'));
        });
    }

    public function test_the_cache_is_separate_for_each_tenant(): void
    {
        Tenancy::run($this->dakar, fn () => Cache::put('stats', 'dakar'));
        Tenancy::run($this->thies, fn () => Cache::remember('stats', null, fn () => 'thies'));
        Cache::put('stats', 'plateforme');

        $this->assertSame('dakar', Tenancy::run($this->dakar, fn () => Cache::get('stats')));
        $this->assertSame('thies', Tenancy::run($this->thies, fn () => Cache::get('stats')));
        $this->assertSame('plateforme', Cache::get('stats'));

        Tenancy::run($this->dakar, fn () => Cache::forget('stats'));
        $this->assertSame('thies', Tenancy::run($this->thies, fn () => Cache::get('stats')));
    }

    public function test_the_middleware_identifies_the_tenant_from_the_url_prefix(): void
    {
        Tenancy::run($this->dakar, fn () => TenantProject::create(['name' => 'Projet Dakar']));
        Tenancy::run($this->thies, fn () => TenantProject::create(['name' => 'Projet Thiès']));

        $this->app->router->group(['prefix' => '/{tenant}', 'middleware' => [IdentifyTenant::class]], function ($router) {
            $router->get('/projets', fn () => Response::json(array_column(TenantProject::all(), 'name')));
        });

        $this->assertSame('["Projet Dakar"]', $this->get('/dakar/projets')->content());
        $this->assertSame('["Projet Thiès"]', $this->get('/thies/projets')->content());
        $this->assertSame(404, $this->get('/inconnu/projets')->status());
        $this->assertNull(Tenancy::current(), 'le locataire ne survit pas à la requête');
    }

    private function onHost(string $uri, string $host): TestResponse
    {
        return new TestResponse($this->app->handle(Request::create('GET', $uri, [], ['HTTP_HOST' => $host])));
    }

    public function test_the_middleware_identifies_the_tenant_from_the_subdomain_domain_or_header(): void
    {
        $this->app->router->domain('{tenant}.plateforme.sn', function ($router) {
            $router->group(['middleware' => [IdentifyTenant::class]], function ($router) {
                $router->get('/qui', fn () => Response::html((string) Tenancy::current()['slug']));
            });
        });
        $this->assertSame('thies', $this->onHost('/qui', 'thies.plateforme.sn')->content());

        $this->app->router->group(['middleware' => [IdentifyTenant::class]], function ($router) {
            $router->get('/moi', fn () => Response::html((string) Tenancy::current()['slug']));
        });

        Config::set('tenancy.identify_by', 'domain');
        $this->assertSame('dakar', $this->onHost('/moi', 'Dakar-Shop.sn:443')->content());
        $this->assertSame(404, $this->onHost('/moi', 'ailleurs.sn')->status());

        Config::set('tenancy.identify_by', 'header');
        $this->assertSame('thies', $this->get('/moi', ['X-Tenant' => 'thies'])->content());
        $this->assertSame(404, $this->get('/moi')->status());
    }

    public function test_a_job_runs_for_the_tenant_that_queued_it(): void
    {
        Config::set('queue.driver', 'file');
        Queue::reset();
        TenantRecordingJob::$seen = [];

        Tenancy::run($this->thies, fn () => Queue::push(new TenantRecordingJob()));
        Queue::push(new TenantRecordingJob());   // hors locataire
        Queue::work();

        sort(TenantRecordingJob::$seen);
        $this->assertSame(['aucun', 'thies'], TenantRecordingJob::$seen);
    }

    public function test_logs_carry_the_tenant(): void
    {
        Tenancy::run($this->dakar, function () {
            $this->assertSame($this->dakar['id'], Log::sharedContext()['tenant']);
        });

        $this->assertArrayNotHasKey('tenant', Log::sharedContext());
    }

    public function test_run_accepts_an_id_and_rejects_an_unknown_one(): void
    {
        $this->assertSame('dakar', Tenancy::run($this->dakar['id'], fn () => Tenancy::current()['slug']));

        $this->expectException(TenancyException::class);
        Tenancy::run(999999, fn () => null);
    }
}

class TenantProject extends Model
{
    protected static string $table = 'np_tenant_projects';
    protected static array $fillable = ['name'];
    protected static bool $softDeletes = true;
    protected static bool $tenantScoped = true;

    public static function tags(int|string $id): array
    {
        return static::belongsToMany($id, TenantTag::class, 'np_tenant_project_tag', 'project_id', 'tag_id');
    }

    public static function eagerLoadable(): array
    {
        return ['tags' => fn (array $projects) => static::loadManyToMany($projects, 'tags', TenantTag::class, 'np_tenant_project_tag', 'project_id', 'tag_id')];
    }
}

class TenantTag extends Model
{
    protected static string $table = 'np_tenant_tags';
    protected static array $fillable = ['label'];
    protected static bool $timestamps = false;
    protected static bool $tenantScoped = true;
}

class TenantRecordingJob extends Job
{
    /** @var list<string> */
    public static array $seen = [];

    public function handle(): void
    {
        self::$seen[] = (string) (Tenancy::current()['slug'] ?? 'aucun');
    }
}
