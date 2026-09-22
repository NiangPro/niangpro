<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use PHPUnit\Framework\TestCase;

/**
 * Le cas d'échec (exit(1)) n'est pas exercé ici pour la même raison que pour doctor() : il tuerait
 * le runner PHPUnit. Vérifié manuellement (voir CHANGELOG) avec DB_CONNECTION=mysql pointant vers
 * un hôte injoignable : sortie "Statut global : error" et code de sortie 1.
 */
class CommanderHealthTest extends TestCase
{
    public function test_reports_every_service_as_ok_in_a_healthy_environment(): void
    {
        $commander = new Commander(dirname(__DIR__, 3));
        $method = new \ReflectionMethod(Commander::class, 'health');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($commander);
        $output = ob_get_clean();

        foreach (['database', 'cache', 'storage', 'queue'] as $service) {
            $this->assertStringContainsString("✓ $service : ok", $output);
        }

        $this->assertStringContainsString('Statut global : ok', $output);
    }
}
