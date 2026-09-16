<?php

namespace Tests\Database;

use Niang\Core\Database\Schema;
use Niang\Core\Testing\TestCase;
use Niang\Core\Validation\Validator;

class ValidatorDatabaseRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_validator_users', function ($table) {
            $table->id();
            $table->string('email')->unique();
        });

        \Niang\Core\Database\DB::insert('INSERT INTO np_validator_users (email) VALUES (?)', ['awa@example.test']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_validator_users');
        parent::tearDown();
    }

    public function test_unique_rule_fails_when_the_value_already_exists(): void
    {
        $rules = ['email' => 'unique:np_validator_users,email'];

        $this->assertTrue(Validator::make(['email' => 'awa@example.test'], $rules)->fails());
        $this->assertFalse(Validator::make(['email' => 'nouvelle@example.test'], $rules)->fails());
    }

    public function test_unique_rule_can_ignore_the_current_row_id(): void
    {
        $row = \Niang\Core\Database\DB::selectOne('SELECT id FROM np_validator_users WHERE email = ?', ['awa@example.test']);
        $rules = ['email' => "unique:np_validator_users,email,{$row['id']}"];

        // Le propriétaire de la ligne peut resoumettre son propre email (cas "modifier mon profil").
        $this->assertFalse(Validator::make(['email' => 'awa@example.test'], $rules)->fails());
    }

    public function test_exists_rule(): void
    {
        $rules = ['email' => 'exists:np_validator_users,email'];

        $this->assertFalse(Validator::make(['email' => 'awa@example.test'], $rules)->fails());
        $this->assertTrue(Validator::make(['email' => 'inconnu@example.test'], $rules)->fails());
    }
}
