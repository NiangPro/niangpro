<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use PHPUnit\Framework\TestCase;

/**
 * Appelle Commander::doctorChecks() (privée) via réflexion plutôt que d'exécuter `niang doctor`
 * dans un vrai sous-processus : Commander::doctor() appelle exit() en cas d'échec, ce qui tuerait
 * le runner PHPUnit, et un sous-processus ferait dépendre le résultat du .env réel de la machine
 * qui exécute les tests (présent en local, absent en CI) plutôt que de la logique de Commander.
 *
 * Les vérifications ".env présent" et "APP_KEY configurée" ne sont pas testées ici pour la même
 * raison : leur verdict dépend de l'état ambiant du dépôt, pas du code de doctorChecks().
 */
class CommanderDoctorTest extends TestCase
{
    /** @return list<array{0: string, 1: string}> */
    private function checks(): array
    {
        $commander = new Commander(dirname(__DIR__, 3));
        $method = new \ReflectionMethod(Commander::class, 'doctorChecks');
        $method->setAccessible(true);

        return $method->invoke($commander);
    }

    private function statusFor(array $checks, string $needle): ?string
    {
        foreach ($checks as [$status, $message]) {
            if (str_contains($message, $needle)) {
                return $status;
            }
        }

        return null;
    }

    public function test_reports_php_version_and_core_extensions_as_ok(): void
    {
        $checks = $this->checks();

        $this->assertSame('ok', $this->statusFor($checks, 'PHP ' . PHP_VERSION));
        $this->assertSame('ok', $this->statusFor($checks, 'Extension pdo'));
        $this->assertSame('ok', $this->statusFor($checks, 'Extension json'));
    }

    public function test_reports_storage_directories_as_writable(): void
    {
        $checks = $this->checks();

        foreach (['storage accessible', 'storage/logs accessible', 'storage/framework accessible'] as $needle) {
            $this->assertSame('ok', $this->statusFor($checks, $needle));
        }
    }

    public function test_includes_a_database_connection_check_for_the_configured_driver(): void
    {
        $checks = $this->checks();
        $status = $this->statusFor($checks, 'Connexion base de données');

        $this->assertNotNull($status);
        $this->assertContains($status, ['ok', 'fail']);
    }

    public function test_warns_without_failing_when_debug_is_enabled_in_production(): void
    {
        $previousEnv = getenv('APP_ENV');
        $previousDebug = getenv('APP_DEBUG');
        putenv('APP_ENV=production');
        putenv('APP_DEBUG=true');

        try {
            $checks = $this->checks();
        } finally {
            $previousEnv === false ? putenv('APP_ENV') : putenv("APP_ENV=$previousEnv");
            $previousDebug === false ? putenv('APP_DEBUG') : putenv("APP_DEBUG=$previousDebug");
        }

        $this->assertSame('warn', $this->statusFor($checks, 'APP_DEBUG=true en production'));
    }

    public function test_does_not_warn_outside_production(): void
    {
        $previousEnv = getenv('APP_ENV');
        putenv('APP_ENV=local');

        try {
            $checks = $this->checks();
        } finally {
            $previousEnv === false ? putenv('APP_ENV') : putenv("APP_ENV=$previousEnv");
        }

        $this->assertNull($this->statusFor($checks, 'APP_DEBUG=true en production'));
    }
}
