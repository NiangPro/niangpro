<?php

namespace Tests\Unit;

use Niang\Core\Config;
use Niang\Core\Container;
use Niang\Core\Event;
use Niang\Core\Exceptions\ConfigurationException;
use Niang\Core\Http\Response;
use Niang\Core\UrlSignature;
use PHPUnit\Framework\TestCase;

/** Cas limites révélés par PHPStan niveau 7 : chacun échouait avant sa correction. */
class RobustnessTest extends TestCase
{
    protected function tearDown(): void
    {
        Event::reset();
        Config::load(base_path());
        parent::tearDown();
    }

    public function test_json_with_invalid_utf8_does_not_break_the_response(): void
    {
        $response = Response::json(['nom' => "Awa \xB1 Diop"]);

        $this->assertSame(['nom' => "Awa \u{FFFD} Diop"], json_decode($response->getContent(), true));
    }

    public function test_an_array_signature_is_rejected_instead_of_crashing(): void
    {
        $previous = getenv('APP_KEY');
        putenv('APP_KEY=' . str_repeat('k', 64));

        try {
            $this->assertFalse(UrlSignature::validate('/reset?email=a%40b.c&signature[]=x'));
            $this->assertFalse(UrlSignature::validate('/reset?signature[a]=x'));
        } finally {
            // Restaurer, pas supprimer : les tests suivants utilisent la clé de .env.testing.
            $previous === false ? putenv('APP_KEY') : putenv("APP_KEY=$previous");
        }
    }

    public function test_container_call_accepts_an_invokable_object(): void
    {
        $invokable = new class () {
            public function __invoke(RobustnessTestDependency $dependency): string
            {
                return $dependency::class;
            }
        };

        $this->assertSame(RobustnessTestDependency::class, (new Container())->call($invokable));
    }

    public function test_a_provider_that_is_not_a_service_provider_is_named_in_the_error(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('stdClass (config/app.php, providers)');

        $app = (new \ReflectionClass(\Niang\Core\Application::class))->newInstanceWithoutConstructor();
        Config::set('app.providers', [\stdClass::class]);
        (new \ReflectionMethod($app, 'bootProviders'))->invoke($app);
    }

    public function test_a_class_listener_without_handle_is_named_in_the_error(): void
    {
        Event::listen('x', RobustnessTestDependency::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(RobustnessTestDependency::class);
        Event::dispatch('x');
    }
}

class RobustnessTestDependency
{
}
