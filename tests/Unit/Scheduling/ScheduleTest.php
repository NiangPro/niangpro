<?php

namespace Tests\Unit\Scheduling;

use Niang\Core\Scheduling\Schedule;
use Niang\Core\Scheduling\ScheduledTask;
use PHPUnit\Framework\TestCase;
use Tests\Support\UsesTempDirectory;

class ScheduleTest extends TestCase
{
    use UsesTempDirectory;

    protected function tearDown(): void
    {
        $this->removeTempDirectories();
        parent::tearDown();
    }

    private function task(): ScheduledTask
    {
        return new ScheduledTask('test', fn () => 0);
    }

    public function test_frequency_helpers_produce_the_expected_cron_expressions(): void
    {
        $this->assertSame('* * * * *', $this->task()->everyMinute()->expression());
        $this->assertSame('*/5 * * * *', $this->task()->everyFiveMinutes()->expression());
        $this->assertSame('0 * * * *', $this->task()->hourly()->expression());
        $this->assertSame('17 * * * *', $this->task()->hourlyAt(17)->expression());
        $this->assertSame('0 0 * * *', $this->task()->daily()->expression());
        $this->assertSame('30 3 * * *', $this->task()->dailyAt('03:30')->expression());
        $this->assertSame('0 8 * * 1', $this->task()->weeklyOn(1, '08:00')->expression());
        $this->assertSame('0 0 1 * *', $this->task()->monthly()->expression());
        $this->assertSame('0 18 15 * *', $this->task()->monthlyOn(15, '18:00')->expression());
        $this->assertSame('0 9 * * 1-5', $this->task()->dailyAt('09:00')->weekdays()->expression());
        $this->assertSame('0 9 * * 0,6', $this->task()->dailyAt('09:00')->weekends()->expression());
    }

    public function test_invalid_times_and_expressions_fail_when_declared(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->task()->dailyAt('25:00');
    }

    public function test_only_due_tasks_are_selected(): void
    {
        $schedule = new Schedule(base_path());
        $schedule->call(fn () => null, 'chaque minute')->everyMinute();
        $schedule->call(fn () => null, 'à 3 h')->dailyAt('03:00');

        $this->assertSame(['chaque minute'], array_map(fn ($t) => $t->description(), $schedule->dueTasks(new \DateTimeImmutable('2026-09-24 13:37'))));
        $this->assertCount(2, $schedule->dueTasks(new \DateTimeImmutable('2026-09-24 03:00')));
    }

    public function test_a_command_runs_in_a_separate_process_and_reports_its_exit_code(): void
    {
        $outputs = [];
        $schedule = new Schedule(base_path(), function (string $output) use (&$outputs): void {
            $outputs[] = $output;
        });

        $ok = $schedule->command('route:list');

        $this->assertSame('niang route:list', $ok->description());
        $this->assertSame(0, $ok->run($this->makeTempDirectory()));
        $this->assertStringContainsString('/up', $outputs[0]);
    }

    public function test_without_overlapping_skips_a_task_whose_previous_run_still_holds_the_lock(): void
    {
        $dir = $this->makeTempDirectory();
        $task = (new ScheduledTask('longue', fn () => 0))->withoutOverlapping();

        // Simule l'exécution précédente toujours en cours : un autre descripteur tient le verrou.
        $lockFile = $dir . '/' . sha1('longue|* * * * *') . '.lock';
        $held = fopen($lockFile, 'c');
        flock($held, LOCK_EX);

        $this->assertNull($task->run($dir));

        flock($held, LOCK_UN);
        fclose($held);

        $this->assertSame(0, $task->run($dir), 'le verrou libéré, la tâche repart');
    }

    public function test_the_lock_is_released_even_when_the_task_throws(): void
    {
        $dir = $this->makeTempDirectory();
        $task = (new ScheduledTask('explose', fn () => throw new \RuntimeException('boum')))->withoutOverlapping();

        try {
            $task->run($dir);
        } catch (\RuntimeException) {
        }

        $again = (new ScheduledTask('explose', fn () => 0))->withoutOverlapping();
        $this->assertSame(0, $again->run($dir));
    }
}
