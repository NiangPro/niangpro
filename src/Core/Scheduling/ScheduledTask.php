<?php

namespace Niang\Core\Scheduling;

/**
 * Une tâche de routes/schedule.php : quoi lancer (commande niang, closure ou job) et quand.
 * Toutes les fréquences se ramènent à une expression cron (voir cron()), affichée par
 * `niang schedule:list`.
 */
final class ScheduledTask
{
    private string $expression = '* * * * *';

    private bool $withoutOverlapping = false;

    /** @param \Closure(): int $runner retourne le code de sortie (0 = succès) */
    public function __construct(private string $description, private \Closure $runner)
    {
    }

    // ---------- Fréquences ----------

    /** Expression cron brute à 5 champs, ex. '30 8 * * 1-5' (8 h 30, du lundi au vendredi). */
    public function cron(string $expression): static
    {
        new CronExpression($expression); // valide tout de suite, pas au premier schedule:run
        $this->expression = $expression;

        return $this;
    }

    public function everyMinute(): static
    {
        return $this->cron('* * * * *');
    }

    public function everyFiveMinutes(): static
    {
        return $this->cron('*/5 * * * *');
    }

    public function everyTenMinutes(): static
    {
        return $this->cron('*/10 * * * *');
    }

    public function everyFifteenMinutes(): static
    {
        return $this->cron('*/15 * * * *');
    }

    public function everyThirtyMinutes(): static
    {
        return $this->cron('*/30 * * * *');
    }

    public function hourly(): static
    {
        return $this->cron('0 * * * *');
    }

    public function hourlyAt(int $minute): static
    {
        return $this->cron("$minute * * * *");
    }

    public function daily(): static
    {
        return $this->cron('0 0 * * *');
    }

    /** @param string $time 'HH:MM' */
    public function dailyAt(string $time): static
    {
        [$hour, $minute] = self::parseTime($time);

        return $this->cron("$minute $hour * * *");
    }

    /** @param int $day 0 = dimanche, 1 = lundi... 6 = samedi */
    public function weeklyOn(int $day, string $time = '00:00'): static
    {
        [$hour, $minute] = self::parseTime($time);

        return $this->cron("$minute $hour * * $day");
    }

    public function weekly(): static
    {
        return $this->weeklyOn(0);
    }

    public function monthlyOn(int $day = 1, string $time = '00:00'): static
    {
        [$hour, $minute] = self::parseTime($time);

        return $this->cron("$minute $hour $day * *");
    }

    public function monthly(): static
    {
        return $this->monthlyOn(1);
    }

    public function weekdays(): static
    {
        return $this->onDays('1-5');
    }

    public function weekends(): static
    {
        return $this->onDays('0,6');
    }

    /**
     * Ne pas relancer la tâche si l'exécution précédente tourne encore (verrou fichier, libéré
     * même si le process meurt). Indispensable pour une tâche qui peut durer plus que son intervalle.
     */
    public function withoutOverlapping(): static
    {
        $this->withoutOverlapping = true;

        return $this;
    }

    // ---------- Exécution ----------

    public function description(): string
    {
        return $this->description;
    }

    public function expression(): string
    {
        return $this->expression;
    }

    public function isDue(\DateTimeInterface $at): bool
    {
        return (new CronExpression($this->expression))->isDue($at);
    }

    public function nextRunAfter(\DateTimeInterface $from): \DateTimeImmutable
    {
        return (new CronExpression($this->expression))->nextRunAfter($from);
    }

    /**
     * Lance la tâche. Retourne son code de sortie, ou null si elle a été sautée parce que
     * l'exécution précédente tourne encore (withoutOverlapping).
     */
    public function run(string $lockDirectory): ?int
    {
        if (!$this->withoutOverlapping) {
            return ($this->runner)();
        }

        if (!is_dir($lockDirectory)) {
            mkdir($lockDirectory, 0755, true);
        }

        $lock = fopen($lockDirectory . '/' . sha1($this->description . '|' . $this->expression) . '.lock', 'c');

        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            return null;
        }

        try {
            return ($this->runner)();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** Ne change que le champ jour de la semaine de l'expression actuelle. */
    private function onDays(string $days): static
    {
        $fields = preg_split('/\s+/', $this->expression) ?: [];
        $fields[4] = $days;

        return $this->cron(implode(' ', $fields));
    }

    /** @return array{0: int, 1: int} */
    private static function parseTime(string $time): array
    {
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $m) !== 1) {
            throw new \InvalidArgumentException("Heure invalide : « $time » (format HH:MM attendu).");
        }

        return [(int) $m[1], (int) $m[2]];
    }
}
