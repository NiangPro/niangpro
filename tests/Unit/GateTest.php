<?php

namespace Tests\Unit;

use Niang\Core\Gate;
use PHPUnit\Framework\TestCase;

class GateTest extends TestCase
{
    public function test_define_and_allows_with_a_closure(): void
    {
        Gate::define('gate-test.always-true', fn (?array $user): bool => true);
        Gate::define('gate-test.always-false', fn (?array $user): bool => false);

        $this->assertTrue(Gate::allows('gate-test.always-true'));
        $this->assertFalse(Gate::allows('gate-test.always-false'));
        $this->assertTrue(Gate::denies('gate-test.always-false'));
    }

    public function test_undefined_ability_denies_by_default(): void
    {
        $this->assertFalse(Gate::allows('gate-test.never-defined'));
        $this->assertTrue(Gate::denies('gate-test.never-defined'));
    }

    public function test_closure_receives_extra_arguments(): void
    {
        Gate::define('gate-test.owns', function (?array $user, array $resource): bool {
            return $resource['owner_id'] === 42;
        });

        $this->assertTrue(Gate::allows('gate-test.owns', ['owner_id' => 42]));
        $this->assertFalse(Gate::allows('gate-test.owns', ['owner_id' => 7]));
    }

    public function test_policy_resolves_dot_notation_ability_to_a_method(): void
    {
        Gate::policy('gate-test-widget', GateTestWidgetPolicy::class);

        $this->assertTrue(Gate::allows('gate-test-widget.publish', ['status' => 'draft']));
        $this->assertFalse(Gate::allows('gate-test-widget.publish', ['status' => 'archived']));
    }

    public function test_policy_method_that_does_not_exist_denies(): void
    {
        Gate::policy('gate-test-widget', GateTestWidgetPolicy::class);

        $this->assertFalse(Gate::allows('gate-test-widget.does-not-exist'));
    }

    public function test_define_takes_precedence_over_a_policy_with_the_same_ability(): void
    {
        Gate::policy('gate-test-widget', GateTestWidgetPolicy::class);
        Gate::define('gate-test-widget.publish', fn (?array $user): bool => false);

        $this->assertFalse(Gate::allows('gate-test-widget.publish', ['status' => 'draft']));
    }
}

class GateTestWidgetPolicy
{
    public function publish(?array $user, array $widget): bool
    {
        return $widget['status'] === 'draft';
    }
}
