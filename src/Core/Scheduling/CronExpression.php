<?php

namespace Niang\Core\Scheduling;

/**
 * Expression cron standard à 5 champs : minute heure jour-du-mois mois jour-de-la-semaine.
 * Chaque champ accepte *, une valeur, une liste (1,15), un intervalle (1-5), un pas (*\/15, 8-18/2).
 * Jour de la semaine : 0 ou 7 = dimanche. Comme cron, si le jour du mois ET le jour de la semaine
 * sont restreints tous les deux, l'un OU l'autre suffit (« le 1er du mois, et tous les lundis »).
 */
final class CronExpression
{
    private const FIELDS = [
        ['minute', 0, 59],
        ['heure', 0, 23],
        ['jour du mois', 1, 31],
        ['mois', 1, 12],
        ['jour de la semaine', 0, 7],
    ];

    /** @var list<list<int>> valeurs autorisées, par champ */
    private array $allowed = [];

    /** @var list<bool> champ restreint (autre chose que « * ») */
    private array $restricted = [];

    public function __construct(private string $expression)
    {
        $parts = preg_split('/\s+/', trim($expression));

        if ($parts === false || count($parts) !== 5) {
            throw new \InvalidArgumentException("Expression cron invalide : « $expression » (5 champs attendus).");
        }

        foreach ($parts as $index => $part) {
            [$name, $min, $max] = self::FIELDS[$index];
            $this->allowed[$index] = self::parseField($part, $min, $max, $name, $expression);
            $this->restricted[$index] = $part !== '*';
        }

        // 7 = dimanche, comme 0.
        if (in_array(7, $this->allowed[4], true)) {
            $this->allowed[4] = array_values(array_unique([...array_diff($this->allowed[4], [7]), 0]));
        }
    }

    public function expression(): string
    {
        return $this->expression;
    }

    public function isDue(\DateTimeInterface $at): bool
    {
        return in_array((int) $at->format('i'), $this->allowed[0], true)
            && in_array((int) $at->format('G'), $this->allowed[1], true)
            && in_array((int) $at->format('n'), $this->allowed[3], true)
            && $this->dayMatches($at);
    }

    /** Prochaine minute (strictement après $from) où l'expression est due. */
    public function nextRunAfter(\DateTimeInterface $from): \DateTimeImmutable
    {
        $at = \DateTimeImmutable::createFromInterface($from)->setTime((int) $from->format('G'), (int) $from->format('i'))->modify('+1 minute');

        // Au plus 5 ans : couvre toute expression valide (le 29 février compris), sans boucle infinie.
        $limit = $at->modify('+5 years');

        while ($at < $limit) {
            if (!in_array((int) $at->format('n'), $this->allowed[3], true) || !$this->dayMatches($at)) {
                $at = $at->modify('+1 day')->setTime(0, 0);
                continue;
            }

            if (!in_array((int) $at->format('G'), $this->allowed[1], true)) {
                $nextHour = $at->modify('+1 hour');
                $at = $nextHour->setTime((int) $nextHour->format('G'), 0);
                continue;
            }

            if (in_array((int) $at->format('i'), $this->allowed[0], true)) {
                return $at;
            }

            $at = $at->modify('+1 minute');
        }

        throw new \RuntimeException("L'expression cron « {$this->expression} » ne correspond à aucune date.");
    }

    private function dayMatches(\DateTimeInterface $at): bool
    {
        $dayOfMonth = in_array((int) $at->format('j'), $this->allowed[2], true);
        $dayOfWeek = in_array((int) $at->format('w'), $this->allowed[4], true);

        if ($this->restricted[2] && $this->restricted[4]) {
            return $dayOfMonth || $dayOfWeek;
        }

        return $dayOfMonth && $dayOfWeek;
    }

    /** @return list<int> */
    private static function parseField(string $field, int $min, int $max, string $name, string $expression): array
    {
        $values = [];

        foreach (explode(',', $field) as $part) {
            if (preg_match('/^(\*|\d+(?:-\d+)?)(?:\/(\d+))?$/', $part, $m) !== 1) {
                throw new \InvalidArgumentException("Expression cron invalide : « $expression » ($name : « $part »).");
            }

            $step = isset($m[2]) ? (int) $m[2] : 1;

            if ($m[1] === '*') {
                [$from, $to] = [$min, $max];
            } elseif (str_contains($m[1], '-')) {
                [$from, $to] = array_map('intval', explode('-', $m[1]));
            } else {
                $from = $to = (int) $m[1];
                // « 5/10 » : de 5 jusqu'au maximum, tous les 10.
                if (isset($m[2])) {
                    $to = $max;
                }
            }

            if ($step < 1 || $from < $min || $to > $max || $from > $to) {
                throw new \InvalidArgumentException("Expression cron invalide : « $expression » ($name hors limites : « $part », attendu $min-$max).");
            }

            for ($value = $from; $value <= $to; $value += $step) {
                $values[] = $value;
            }
        }

        sort($values);

        return array_values(array_unique($values));
    }
}
