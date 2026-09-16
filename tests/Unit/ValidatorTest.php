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

    public function test_nullable_skips_other_rules_when_empty_but_not_when_filled(): void
    {
        $this->assertFalse(Validator::make(['bio' => null], ['bio' => 'nullable|min:10'])->fails());
        $this->assertFalse(Validator::make([], ['bio' => 'nullable|min:10'])->fails());
        $this->assertTrue(Validator::make(['bio' => 'court'], ['bio' => 'nullable|min:10'])->fails());
    }

    public function test_boolean_accepts_common_truthy_falsy_representations(): void
    {
        foreach ([true, false, 0, 1, '0', '1', 'true', 'false'] as $value) {
            $this->assertFalse(Validator::make(['active' => $value], ['active' => 'boolean'])->fails());
        }

        $this->assertTrue(Validator::make(['active' => 'oui'], ['active' => 'boolean'])->fails());
    }

    public function test_array_rule(): void
    {
        $this->assertFalse(Validator::make(['tags' => ['a', 'b']], ['tags' => 'array'])->fails());
        $this->assertTrue(Validator::make(['tags' => 'a,b'], ['tags' => 'array'])->fails());
    }

    public function test_url_rule(): void
    {
        $this->assertFalse(Validator::make(['site' => 'https://niangpro.dev'], ['site' => 'url'])->fails());
        $this->assertTrue(Validator::make(['site' => 'pas une url'], ['site' => 'url'])->fails());
    }

    public function test_date_and_date_format_rules(): void
    {
        $this->assertFalse(Validator::make(['d' => '2026-01-15'], ['d' => 'date'])->fails());
        $this->assertTrue(Validator::make(['d' => 'pas une date'], ['d' => 'date'])->fails());

        $this->assertFalse(Validator::make(['d' => '15/01/2026'], ['d' => 'date_format:d/m/Y'])->fails());
        $this->assertTrue(Validator::make(['d' => '2026-01-15'], ['d' => 'date_format:d/m/Y'])->fails());
    }

    public function test_between_rule(): void
    {
        $this->assertFalse(Validator::make(['age' => 25], ['age' => 'between:18,65'])->fails());
        $this->assertTrue(Validator::make(['age' => 17], ['age' => 'between:18,65'])->fails());
        $this->assertTrue(Validator::make(['name' => 'Al'], ['name' => 'between:3,10'])->fails());
    }

    public function test_in_and_not_in_rules(): void
    {
        $this->assertFalse(Validator::make(['role' => 'admin'], ['role' => 'in:admin,user'])->fails());
        $this->assertTrue(Validator::make(['role' => 'guest'], ['role' => 'in:admin,user'])->fails());
        $this->assertFalse(Validator::make(['role' => 'guest'], ['role' => 'not_in:admin,user'])->fails());
    }

    public function test_same_and_different_rules(): void
    {
        $data = ['email' => 'a@b.com', 'email_repeat' => 'a@b.com'];
        $this->assertFalse(Validator::make($data, ['email_repeat' => 'same:email'])->fails());

        $data['email_repeat'] = 'autre@b.com';
        $this->assertTrue(Validator::make($data, ['email_repeat' => 'same:email'])->fails());
        $this->assertFalse(Validator::make($data, ['email_repeat' => 'different:email'])->fails());
    }

    public function test_required_if_only_applies_when_condition_matches(): void
    {
        $rules = ['company' => 'required_if:type,pro'];

        $this->assertFalse(Validator::make(['type' => 'perso'], $rules)->fails());
        $this->assertTrue(Validator::make(['type' => 'pro'], $rules)->fails());
        $this->assertFalse(Validator::make(['type' => 'pro', 'company' => 'Acme'], $rules)->fails());
    }

    public function test_required_with_and_required_without(): void
    {
        $this->assertTrue(Validator::make(['a' => 'x'], ['b' => 'required_with:a'])->fails());
        $this->assertFalse(Validator::make([], ['b' => 'required_with:a'])->fails());

        $this->assertTrue(Validator::make([], ['b' => 'required_without:a'])->fails());
        $this->assertFalse(Validator::make(['a' => 'x'], ['b' => 'required_without:a'])->fails());
    }

    public function test_custom_message_overrides_the_default(): void
    {
        try {
            Validator::make([], ['email' => 'required'], ['email.required' => 'Indique ton email !'])->validate();
            $this->fail('ValidationException attendue.');
        } catch (ValidationException $e) {
            $this->assertSame(['email' => ['Indique ton email !']], $e->errors);
        }
    }

    public function test_custom_attribute_name_is_used_in_default_messages(): void
    {
        try {
            Validator::make([], ['email' => 'required'], [], ['email' => 'Adresse email'])->validate();
            $this->fail('ValidationException attendue.');
        } catch (ValidationException $e) {
            $this->assertSame(['email' => ['Le champ Adresse email est requis.']], $e->errors);
        }
    }

    public function test_wildcard_validates_each_item_of_an_array(): void
    {
        $data = ['items' => [['name' => 'Awa'], ['name' => '']]];

        try {
            Validator::make($data, ['items.*.name' => 'required|string'])->validate();
            $this->fail('ValidationException attendue.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('items.1.name', $e->errors);
            $this->assertArrayNotHasKey('items.0.name', $e->errors);
        }
    }

    public function test_wildcard_on_simple_array_values(): void
    {
        $this->assertFalse(Validator::make(['tags' => ['a', 'bb']], ['tags.*' => 'string|min:1'])->fails());
        $this->assertTrue(Validator::make(['tags' => ['a', '']], ['tags.*' => 'required'])->fails());
    }
}
