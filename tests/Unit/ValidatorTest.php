<?php

namespace Tests\Unit;

use Niang\Core\Validation\ValidationException;
use Niang\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function test_required_field_fails_when_missing(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make([], ['email' => 'required'])->validate();
    }

    public function test_valid_data_passes_and_returns_only_validated_fields(): void
    {
        $result = Validator::make(
            ['email' => 'a@b.com', 'ignored' => 'x'],
            ['email' => 'required|email']
        )->validate();

        $this->assertSame(['email' => 'a@b.com'], $result);
    }

    public function test_fails_helper_does_not_throw(): void
    {
        $this->assertTrue(Validator::make([], ['name' => 'required'])->fails());
        $this->assertFalse(Validator::make(['name' => 'Awa'], ['name' => 'required'])->fails());
    }

    public function test_min_rule_on_strings_checks_length(): void
    {
        $this->assertTrue(Validator::make(['name' => 'A'], ['name' => 'min:2'])->fails());
        $this->assertFalse(Validator::make(['name' => 'Aw'], ['name' => 'min:2'])->fails());
    }

    public function test_confirmed_rule_compares_confirmation_field(): void
    {
        $data = ['password' => 'secret', 'password_confirmation' => 'secret'];
        $this->assertFalse(Validator::make($data, ['password' => 'confirmed'])->fails());

        $data['password_confirmation'] = 'autre';
        $this->assertTrue(Validator::make($data, ['password' => 'confirmed'])->fails());
    }
}
