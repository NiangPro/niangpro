<?php

namespace Tests\Security;

use Niang\Core\Database\QueryBuilder;
use Niang\Core\Database\Schema;
use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Testing\TestCase;

/** Roadmap §55 : injection SQL et XSS. */
class InjectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('np_sec_items', function ($table) {
            $table->id();
            $table->string('name');
        });
        (new QueryBuilder('np_sec_items'))->insert(['name' => 'a']);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('np_sec_items');
        parent::tearDown();
    }

    public function test_values_are_always_bound_never_interpolated(): void
    {
        $payload = "x'); DROP TABLE np_sec_items; --";
        (new QueryBuilder('np_sec_items'))->insert(['name' => $payload]);

        $this->assertSame($payload, (new QueryBuilder('np_sec_items'))->where('name', $payload)->first()['name']);
        $this->assertSame([], (new QueryBuilder('np_sec_items'))->where('name', "a' OR '1'='1")->get());
        $this->assertSame(2, (new QueryBuilder('np_sec_items'))->count(), 'table intacte');
    }

    /** @dataProvider identifierInjections */
    public function test_identifiers_and_operators_from_user_input_are_rejected(\Closure $query): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $query(new QueryBuilder('np_sec_items'));
    }

    /** @return array<string, array{0: \Closure}> */
    public static function identifierInjections(): array
    {
        $tri = 'name; DROP TABLE np_sec_items';

        return [
            'orderBy($_GET)' => [fn (QueryBuilder $q) => $q->orderBy($tri)],
            'sens de tri' => [fn (QueryBuilder $q) => $q->orderBy('name', 'asc; DROP TABLE x')],
            'where colonne' => [fn (QueryBuilder $q) => $q->where('1=1 OR name', 'x')],
            'where opérateur' => [fn (QueryBuilder $q) => $q->where('name', '= 1 OR 1 =', 1)],
            'update($_POST)' => [fn (QueryBuilder $q) => $q->where('id', 1)->update(['name = (SELECT 1), name' => 'x'])],
            'insert clés' => [fn (QueryBuilder $q) => $q->insert(['name) VALUES (1); --' => 'x'])],
            'pluck' => [fn (QueryBuilder $q) => $q->pluck('name FROM users --')],
            'curseur colonne' => [fn (QueryBuilder $q) => $q->cursorPaginate(10, null, 'id OR 1=1')],
        ];
    }

    public function test_e_escapes_html_and_attributes(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
        $this->assertSame('&quot; onmouseover=&quot;alert(1)', e('" onmouseover="alert(1)'));
        $this->assertSame('&#039;', e("'"));
    }

    public function test_json_for_html_cannot_close_a_script_or_attribute(): void
    {
        $encoded = json_for_html(['x' => '</script><script>alert(1)</script>', 'y' => '" onload="x']);

        $this->assertStringNotContainsString('</script>', $encoded);
        $this->assertStringNotContainsString('"', $encoded);
    }

    public function test_validation_errors_and_old_input_are_escaped_in_views(): void
    {
        $this->app->router->get('/sec-echo', fn (Request $request) => Response::html('<p>' . e($request->input('q')) . '</p>'));

        $this->get('/sec-echo?q=' . rawurlencode('<img src=x onerror=alert(1)>'))
            ->assertDontSee('<img src=x')
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;');
    }
}
