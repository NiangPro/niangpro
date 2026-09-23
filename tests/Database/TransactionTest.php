<?php

namespace Tests\Database;

use Niang\Core\Database\DB;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Testing\TestCase;

/**
 * DB::transaction()/beginTransaction()/commit()/rollBack() n'avaient jusqu'ici aucun test direct.
 * Bug réel découvert en écrivant ce jalon (pas hypothétique) : un test utilisant RefreshDatabase
 * (qui ouvre sa propre transaction) plantait avec PDOException("There is already an active
 * transaction") dès que le code testé appelait lui-même DB::transaction() — PDO ne permet qu'une
 * seule transaction active à la fois. Fix : imbrication réelle via SAVEPOINT.
 */
class TransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_test_ledger', function ($table) {
            $table->id();
            $table->string('label');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_test_ledger');
        parent::tearDown();
    }

    public function test_a_committed_transaction_persists_its_writes(): void
    {
        DB::transaction(function () {
            $this->insert('A');
        });

        $this->assertSame(['A'], $this->labels());
    }

    public function test_an_exception_inside_a_transaction_rolls_back_and_still_propagates(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        try {
            DB::transaction(function () {
                $this->insert('A');
                throw new \RuntimeException('boom');
            });
        } finally {
            $this->assertSame([], $this->labels());
        }
    }

    /**
     * La reproduction directe du bug : sans imbrication réelle, ce second beginTransaction()
     * lève PDOException("There is already an active transaction").
     */
    public function test_transactions_can_be_nested_without_crashing(): void
    {
        DB::transaction(function () {
            $this->insert('outer');

            DB::transaction(function () {
                $this->insert('inner');
            });
        });

        $this->assertSame(['outer', 'inner'], $this->labels());
    }

    /**
     * La vraie valeur d'une imbrication par SAVEPOINT (pas seulement "ne plante plus") : une
     * exception dans la transaction interne n'annule que ses propres écritures, pas celles déjà
     * faites par la transaction englobante avant ou après.
     */
    public function test_a_failed_inner_transaction_only_discards_its_own_writes(): void
    {
        DB::transaction(function () {
            $this->insert('avant');

            try {
                DB::transaction(function () {
                    $this->insert('perdu');
                    throw new \RuntimeException('boom');
                });
            } catch (\RuntimeException) {
                // Volontairement avalée ici : on vérifie que la transaction externe continue.
            }

            $this->insert('apres');
        });

        $this->assertSame(['avant', 'apres'], $this->labels());
    }

    public function test_in_transaction_reports_the_current_nesting_state(): void
    {
        $this->assertFalse(DB::inTransaction());

        DB::transaction(function () {
            $this->assertTrue(DB::inTransaction());

            DB::transaction(function () {
                $this->assertTrue(DB::inTransaction());
            });

            $this->assertTrue(DB::inTransaction());
        });

        $this->assertFalse(DB::inTransaction());
    }

    /** Le bug tel que rencontré : RefreshDatabase ouvre sa transaction avant le test lui-même. */
    public function test_nesting_under_a_refresh_database_style_outer_transaction(): void
    {
        DB::beginTransaction(); // simule RefreshDatabase::refreshDatabase()

        try {
            DB::transaction(function () {
                $this->insert('depuis-le-code-testé');
            });

            $this->assertSame(['depuis-le-code-testé'], $this->labels());
        } finally {
            DB::rollBack(); // simule RefreshDatabase::rollbackRefreshedDatabase()
        }

        $this->assertSame([], $this->labels());
    }

    private function insert(string $label): void
    {
        (new QueryBuilder('np_test_ledger'))->insert(['label' => $label]);
    }

    /** @return list<string> */
    private function labels(): array
    {
        return array_column((new QueryBuilder('np_test_ledger'))->orderBy('id')->get(), 'label');
    }
}
