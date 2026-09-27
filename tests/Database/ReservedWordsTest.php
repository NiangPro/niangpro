<?php

namespace Tests\Database;

use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Testing\TestCase;

/** Colonnes et table nommées comme des mots réservés SQL : exécution réelle sur les trois moteurs. */
class ReservedWordsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('np_order', function ($table) {
            $table->id();
            $table->integer('rank');
            $table->string('key');
            $table->string('group')->nullable();
            $table->integer('order')->default(0);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_order');
        parent::tearDown();
    }

    public function test_every_query_builder_operation_quotes_reserved_words(): void
    {
        $q = fn () => new QueryBuilder('np_order');

        $q()->insert(['rank' => 2, 'key' => 'b', 'group' => 'x']);
        $q()->insert(['rank' => 1, 'key' => 'a', 'group' => 'x']);
        $q()->where('key', 'a')->update(['group' => 'y']);
        $q()->where('rank', 2)->increment('order', 5);

        $this->assertSame(['a', 'b'], $q()->orderBy('rank')->pluck('key'));
        $this->assertSame(1, $q()->where('group', 'y')->count());
        $this->assertSame(5, (int) $q()->where('key', 'b')->first()['order']);
        $this->assertSame(3, (int) $q()->sum('rank'));
        $this->assertCount(2, $q()->select('group')->groupBy('group')->get());
        $this->assertSame(['b'], $q()->whereIn('rank', [2])->whereNotNull('group')->pluck('key'));

        $q()->where('key', 'a')->delete();
        $this->assertSame(1, $q()->count());
    }
}
