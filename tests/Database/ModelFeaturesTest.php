<?php

namespace Tests\Database;

use Niang\Core\Database\DB;
use Niang\Core\Database\Model;
use Niang\Core\Database\Schema;
use Niang\Core\Exceptions\DatabaseException;
use Niang\Core\Testing\TestCase;

/** $timestamps, $casts et $softDeletes, contre une vraie base (SQLite en mémoire, MySQL/PostgreSQL en CI). */
class ModelFeaturesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_test_articles', function ($table) {
            $table->id();
            $table->string('title');
            $table->boolean('published')->default(false);
            $table->integer('views')->default(0);
            $table->decimal('rating', 4, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('np_test_notes', function ($table) {
            $table->id();
            $table->foreignId('article_id');
            $table->string('body');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('np_test_labels', function ($table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('np_test_article_label', function ($table) {
            $table->foreignId('article_id');
            $table->foreignId('label_id');
        });
    }

    protected function tearDown(): void
    {
        foreach (['np_test_article_label', 'np_test_labels', 'np_test_notes', 'np_test_articles'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_create_fills_both_timestamps_with_the_php_clock(): void
    {
        $before = date('Y-m-d H:i:s');
        $article = FeatureArticle::find(FeatureArticle::create(['title' => 'Bonjour']));

        $this->assertGreaterThanOrEqual($before, $article['created_at']);
        $this->assertSame($article['created_at'], $article['updated_at']);
    }

    public function test_update_refreshes_updated_at_but_not_created_at(): void
    {
        $id = FeatureArticle::forceCreate(['title' => 'Avant', 'created_at' => '2020-01-01 10:00:00', 'updated_at' => '2020-01-01 10:00:00']);

        FeatureArticle::update($id, ['title' => 'Après']);

        $article = FeatureArticle::find($id);
        $this->assertSame('2020-01-01 10:00:00', $article['created_at']);
        $this->assertNotSame('2020-01-01 10:00:00', $article['updated_at']);
    }

    public function test_an_explicit_timestamp_wins(): void
    {
        $id = FeatureArticle::create(['title' => 'Importé', 'created_at' => '2019-05-05 08:00:00']);

        $this->assertSame('2019-05-05 08:00:00', FeatureArticle::find($id)['created_at']);
    }

    public function test_a_table_without_timestamps_gets_a_clear_error(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('$timestamps = false');

        FeatureLabelWithTimestamps::create(['name' => 'php']);
    }

    public function test_casts_convert_values_read_from_the_database(): void
    {
        $id = FeatureArticle::create(['title' => 'Typé', 'published' => true, 'views' => '42', 'rating' => '4.5', 'meta' => ['tags' => ['php', 'sql'], 'auteur' => 'Awa']]);

        $article = FeatureArticle::find($id);

        $this->assertTrue($article['published']);
        $this->assertSame(42, $article['views']);
        $this->assertSame(4.5, $article['rating']);
        $this->assertSame(['tags' => ['php', 'sql'], 'auteur' => 'Awa'], $article['meta']);
    }

    public function test_json_and_bool_casts_are_stored_in_a_portable_form(): void
    {
        $id = FeatureArticle::create(['title' => 'Stockage', 'published' => false, 'meta' => ['é' => 'à']]);

        $raw = DB::selectOne('SELECT published, meta FROM np_test_articles WHERE id = ?', [$id]);

        $this->assertSame(0, (int) $raw['published']);
        $this->assertSame(['é' => 'à'], json_decode($raw['meta'], true));
    }

    public function test_null_values_are_not_cast(): void
    {
        $article = FeatureArticle::find(FeatureArticle::create(['title' => 'Vide']));

        $this->assertNull($article['rating']);
        $this->assertNull($article['meta']);
    }

    public function test_casts_apply_to_every_read_path(): void
    {
        FeatureArticle::create(['title' => 'A', 'views' => '7']);

        $this->assertSame(7, FeatureArticle::all()[0]['views']);
        $this->assertSame(7, FeatureArticle::where('title', 'A')[0]['views']);
        $this->assertSame(7, FeatureArticle::query()->first()['views']);
        $this->assertSame(7, FeatureArticle::paginate(10)->items[0]['views']);
    }

    public function test_soft_deleted_rows_disappear_from_every_read_but_stay_in_the_table(): void
    {
        $kept = FeatureArticle::create(['title' => 'Gardé']);
        $deleted = FeatureArticle::create(['title' => 'Supprimé']);

        $this->assertTrue(FeatureArticle::destroy($deleted));

        $this->assertNull(FeatureArticle::find($deleted));
        $this->assertSame([(int) $kept], array_map('intval', array_column(FeatureArticle::all(), 'id')));
        $this->assertSame(1, FeatureArticle::query()->count());
        $this->assertSame(1, FeatureArticle::paginate(10)->total);
        $this->assertNotNull(DB::selectOne('SELECT id FROM np_test_articles WHERE id = ?', [$deleted]));
    }

    public function test_trashed_rows_can_be_listed_restored_and_really_deleted(): void
    {
        $id = FeatureArticle::create(['title' => 'Corbeille']);
        FeatureArticle::destroy($id);

        $this->assertSame(['Corbeille'], array_column(FeatureArticle::onlyTrashed()->get(), 'title'));
        $this->assertSame(1, FeatureArticle::withTrashed()->count());

        FeatureArticle::restore($id);
        $this->assertSame('Corbeille', FeatureArticle::find($id)['title']);
        $this->assertSame(0, FeatureArticle::onlyTrashed()->count());

        FeatureArticle::forceDestroy($id);
        $this->assertNull(DB::selectOne('SELECT id FROM np_test_articles WHERE id = ?', [$id]));
    }

    public function test_destroying_twice_does_not_move_the_deletion_date(): void
    {
        $id = FeatureArticle::create(['title' => 'Deux fois']);
        FeatureArticle::destroy($id);
        DB::statement('UPDATE np_test_articles SET deleted_at = ? WHERE id = ?', ['2020-01-01 00:00:00', $id]);

        $this->assertTrue(FeatureArticle::destroy($id));

        $this->assertSame('2020-01-01 00:00:00', FeatureArticle::onlyTrashed()->first()['deleted_at']);
    }

    public function test_relations_and_eager_loading_ignore_soft_deleted_rows(): void
    {
        $articleId = FeatureArticle::create(['title' => 'Parent']);
        $visible = FeatureNote::create(['article_id' => $articleId, 'body' => 'visible']);
        $hidden = FeatureNote::create(['article_id' => $articleId, 'body' => 'cachée']);
        FeatureNote::destroy($hidden);

        $this->assertSame(['visible'], array_column(FeatureArticle::notes($articleId), 'body'));

        $loaded = FeatureArticle::with('notes')->get();
        $this->assertSame(['visible'], array_column($loaded[0]['notes'], 'body'));
        $this->assertSame((int) $visible, (int) $loaded[0]['notes'][0]['id']);
    }

    public function test_many_to_many_relations_ignore_soft_deleted_related_rows_and_apply_casts(): void
    {
        $labelId = (int) FeatureLabel::create(['name' => 'php']);
        $live = (int) FeatureArticle::create(['title' => 'Visible', 'views' => '3']);
        $gone = (int) FeatureArticle::create(['title' => 'Supprimé']);
        DB::statement('INSERT INTO np_test_article_label (article_id, label_id) VALUES (?, ?), (?, ?)', [$live, $labelId, $gone, $labelId]);
        FeatureArticle::destroy($gone);

        $articles = FeatureLabel::articles($labelId);
        $this->assertSame(['Visible'], array_column($articles, 'title'));
        $this->assertSame(3, $articles[0]['views']);

        $labels = FeatureLabel::with('articles')->get();
        $this->assertSame(['Visible'], array_column($labels[0]['articles'], 'title'));
        $this->assertSame(3, $labels[0]['articles'][0]['views']);
    }
}

class FeatureArticle extends Model
{
    protected static string $table = 'np_test_articles';
    protected static array $fillable = ['title', 'published', 'views', 'rating', 'meta', 'created_at'];
    protected static array $casts = ['published' => 'bool', 'views' => 'int', 'rating' => 'float', 'meta' => 'json'];
    protected static bool $softDeletes = true;

    public static function notes(int|string $id): array
    {
        return static::hasMany($id, FeatureNote::class, 'article_id');
    }

    public static function eagerLoadable(): array
    {
        return ['notes' => fn (array $articles) => static::loadMany($articles, 'notes', FeatureNote::class, 'article_id')];
    }
}

class FeatureNote extends Model
{
    protected static string $table = 'np_test_notes';
    protected static array $fillable = ['article_id', 'body'];
    protected static bool $softDeletes = true;
}

class FeatureLabel extends Model
{
    protected static string $table = 'np_test_labels';
    protected static array $fillable = ['name'];
    protected static bool $timestamps = false;

    public static function articles(int|string $id): array
    {
        return static::belongsToMany($id, FeatureArticle::class, 'np_test_article_label', 'label_id', 'article_id');
    }

    public static function eagerLoadable(): array
    {
        return ['articles' => fn (array $labels) => static::loadManyToMany($labels, 'articles', FeatureArticle::class, 'np_test_article_label', 'label_id', 'article_id')];
    }
}

class FeatureLabelWithTimestamps extends Model
{
    protected static string $table = 'np_test_labels';
    protected static array $fillable = ['name'];
}
