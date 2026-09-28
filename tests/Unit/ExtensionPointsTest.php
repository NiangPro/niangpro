<?php

namespace Tests\Unit;

use Niang\Core\Application;
use Niang\Core\Config;
use Niang\Core\Contracts\ShouldQueue;
use Niang\Core\Event;
use Niang\Core\Log;
use PHPUnit\Framework\TestCase;

/** Points d'extension par lesquels le framework assemble ses composants (ADR 0012). */
class ExtensionPointsTest extends TestCase
{
    protected function setUp(): void
    {
        Config::load(base_path());
        Config::set('logging.channel', 'daily');
        Event::reset();
    }

    protected function tearDown(): void
    {
        Log::contextUsing('essai', static fn (): array => []);
        Application::wire();
        Event::reset();
        Config::load(base_path());
    }

    public function test_log_context_providers_are_called_for_every_message_and_replaced_by_name(): void
    {
        $file = base_path('storage/logs/' . date('Y-m-d') . '.log');
        $calls = 0;
        Log::contextUsing('essai', function () use (&$calls): array {
            $calls++;

            return ['boutique' => 'dakar'];
        });
        Log::contextUsing('essai', function () use (&$calls): array {
            $calls++;

            return ['boutique' => 'thies'];
        });

        $offset = is_file($file) ? (int) filesize($file) : 0;
        Log::info('np-extension-1');
        Log::info('np-extension-2');
        clearstatcache();
        $written = substr((string) file_get_contents($file), $offset);

        $this->assertSame(2, $calls, 'le second fournisseur a remplacé le premier');
        $this->assertSame(2, substr_count($written, '"boutique":"thies"'));
        $this->assertStringNotContainsString('dakar', $written);
    }

    public function test_a_should_queue_listener_runs_at_once_when_no_queue_is_installed(): void
    {
        Event::queueUsing(null);
        ExtensionQueuedListener::$handled = 0;
        Event::listen('np.extension', ExtensionQueuedListener::class);

        Event::dispatch('np.extension', 'x');

        $this->assertSame(1, ExtensionQueuedListener::$handled);
    }

    public function test_the_framework_queues_should_queue_listeners(): void
    {
        $queued = [];
        Event::queueUsing(function (string $listener, array $payload) use (&$queued): void {
            $queued[] = [$listener, $payload];
        });
        ExtensionQueuedListener::$handled = 0;
        Event::listen('np.extension', ExtensionQueuedListener::class);

        Event::dispatch('np.extension', 'x');

        $this->assertSame(0, ExtensionQueuedListener::$handled);
        $this->assertSame([[ExtensionQueuedListener::class, ['x']]], $queued);
    }
}

class ExtensionQueuedListener implements ShouldQueue
{
    public static int $handled = 0;

    public function handle(mixed ...$payload): void
    {
        self::$handled++;
    }
}
