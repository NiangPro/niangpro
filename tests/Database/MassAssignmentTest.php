<?php

namespace Tests\Database;

use Niang\Core\Database\Model;
use Niang\Core\Database\Schema;
use Niang\Core\Exceptions\MassAssignmentException;
use Niang\Core\Testing\TestCase;

class MassAssignmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('np_test_members', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('role')->default('user');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_test_members');
        parent::tearDown();
    }

    public function test_create_ignores_columns_outside_fillable(): void
    {
        // Ce qu'enverrait un visiteur qui ajoute un champ caché au formulaire d'inscription.
        $id = MassAssignmentMember::create(['name' => 'Awa', 'role' => 'admin', '_token' => 'abc']);

        $this->assertSame('user', MassAssignmentMember::find($id)['role']);
        $this->assertSame('Awa', MassAssignmentMember::find($id)['name']);
    }

    public function test_update_ignores_columns_outside_fillable(): void
    {
        $id = MassAssignmentMember::create(['name' => 'Awa']);

        MassAssignmentMember::update($id, ['name' => 'Awa Diop', 'role' => 'admin']);

        $member = MassAssignmentMember::find($id);
        $this->assertSame('Awa Diop', $member['name']);
        $this->assertSame('user', $member['role']);
    }

    public function test_force_create_and_force_update_bypass_fillable_for_trusted_code(): void
    {
        $id = MassAssignmentMember::forceCreate(['name' => 'Admin', 'role' => 'admin']);
        $this->assertSame('admin', MassAssignmentMember::find($id)['role']);

        MassAssignmentMember::forceUpdate($id, ['role' => 'user']);
        $this->assertSame('user', MassAssignmentMember::find($id)['role']);
    }

    public function test_a_model_without_fillable_refuses_create_instead_of_accepting_everything(): void
    {
        $this->expectException(MassAssignmentException::class);
        $this->expectExceptionMessage('$fillable');

        MassAssignmentUnguardedMember::create(['name' => 'Awa', 'role' => 'admin']);
    }

    public function test_data_with_no_fillable_column_fails_clearly_instead_of_an_obscure_sql_error(): void
    {
        $this->expectException(MassAssignmentException::class);
        $this->expectExceptionMessage('role');

        MassAssignmentMember::update(1, ['role' => 'admin']);
    }

    public function test_factories_are_trusted_code(): void
    {
        [$id] = MassAssignmentMember::factory(fn () => ['name' => 'Seed', 'role' => 'admin'])->create();

        $this->assertSame('admin', MassAssignmentMember::find($id)['role']);
    }
}

class MassAssignmentMember extends Model
{
    protected static string $table = 'np_test_members';
    protected static array $fillable = ['name'];
}

class MassAssignmentUnguardedMember extends Model
{
    protected static string $table = 'np_test_members';
}
