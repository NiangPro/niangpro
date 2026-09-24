<?php

namespace Niang\Core\Scheduling;

use Niang\Core\Job;
use Niang\Core\Queue;

/**
 * Les tâches planifiées, déclarées dans routes/schedule.php (qui reçoit $schedule) :
 *
 *   $schedule->command('queue:work')->everyMinute()->withoutOverlapping();
 *   $schedule->command('db:seed', ['Tags'])->dailyAt('03:00');
 *   $schedule->call(fn () => Cache::forget('stats'))->hourly();
 *   $schedule->job(new SendWeeklyReportJob())->weeklyOn(1, '08:00');
 *
 * Une seule ligne cron sur le serveur suffit ensuite : `* * * * * php bin/niang schedule:run`.
 */
final class Schedule
{
    /** @var list<ScheduledTask> */
    private array $tasks = [];

    /** @param \Closure(string): void $output reçoit la sortie des commandes lancées */
    public function __construct(private string $basePath, private ?\Closure $output = null)
    {
    }

    /**
     * Une commande niang, lancée dans un process séparé : une commande qui plante ou appelle exit()
     * n'interrompt pas les autres tâches de la même minute.
     *
     * @param list<string> $arguments
     */
    public function command(string $command, array $arguments = []): ScheduledTask
    {
        $description = trim('niang ' . $command . ' ' . implode(' ', $arguments));

        return $this->add(new ScheduledTask($description, function () use ($command, $arguments): int {
            // stdout et stderr dans un même fichier temporaire : l'ordre des messages est gardé, et
            // aucun risque de blocage si la commande remplit l'un des deux tuyaux.
            $log = tmpfile();

            if ($log === false) {
                return 1;
            }

            $process = proc_open(
                [PHP_BINARY, $this->basePath . '/bin/niang', $command, ...$arguments],
                [1 => $log, 2 => $log],
                $pipes,
                $this->basePath
            );

            if (!is_resource($process)) {
                fclose($log);
                return 1;
            }

            $code = proc_close($process);
            rewind($log);
            $output = (string) stream_get_contents($log);
            fclose($log);

            if ($this->output !== null && trim($output) !== '') {
                ($this->output)($output);
            }

            return $code;
        }));
    }

    /** Une closure, exécutée dans le process de schedule:run ; une exception compte comme un échec. */
    public function call(\Closure $callback, string $description = 'closure'): ScheduledTask
    {
        return $this->add(new ScheduledTask($description, function () use ($callback): int {
            $callback();

            return 0;
        }));
    }

    /** Pousse un job sur la file (traité ensuite par queue:work) plutôt que de l'exécuter ici. */
    public function job(Job $job): ScheduledTask
    {
        return $this->add(new ScheduledTask('job ' . $job::class, function () use ($job): int {
            Queue::push(clone $job);

            return 0;
        }));
    }

    /** @return list<ScheduledTask> */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /** @return list<ScheduledTask> */
    public function dueTasks(\DateTimeInterface $at): array
    {
        return array_values(array_filter($this->tasks, fn (ScheduledTask $task) => $task->isDue($at)));
    }

    private function add(ScheduledTask $task): ScheduledTask
    {
        return $this->tasks[] = $task;
    }
}
