<?php

namespace Tests\Unit\Scheduling;

use Niang\Core\Scheduling\CronExpression;
use PHPUnit\Framework\TestCase;

class CronExpressionTest extends TestCase
{
    private static function at(string $datetime): \DateTimeImmutable
    {
        return new \DateTimeImmutable($datetime);
    }

    public function test_every_minute_is_always_due(): void
    {
        $this->assertTrue((new CronExpression('* * * * *'))->isDue(self::at('2026-09-24 13:37')));
    }

    public function test_fixed_values_lists_ranges_and_steps(): void
    {
        $cron = new CronExpression('*/15 8-18/2 * * 1-5');

        $this->assertTrue($cron->isDue(self::at('2026-09-24 08:45')));   // jeudi
        $this->assertTrue($cron->isDue(self::at('2026-09-24 18:00')));
        $this->assertFalse($cron->isDue(self::at('2026-09-24 09:00')));  // heure impaire
        $this->assertFalse($cron->isDue(self::at('2026-09-24 08:10')));
        $this->assertFalse($cron->isDue(self::at('2026-09-26 08:00')));  // samedi

        $list = new CronExpression('0,30 12 1,15 * *');
        $this->assertTrue($list->isDue(self::at('2026-10-15 12:30')));
        $this->assertFalse($list->isDue(self::at('2026-10-16 12:30')));
    }

    public function test_sunday_is_both_0_and_7(): void
    {
        $sunday = self::at('2026-09-27 10:00');

        $this->assertTrue((new CronExpression('0 10 * * 0'))->isDue($sunday));
        $this->assertTrue((new CronExpression('0 10 * * 7'))->isDue($sunday));
        $this->assertTrue((new CronExpression('0 10 * * 5-7'))->isDue($sunday));
    }

    public function test_day_of_month_or_day_of_week_when_both_are_restricted(): void
    {
        $cron = new CronExpression('0 9 1 * 1'); // le 1er du mois, ET chaque lundi

        $this->assertTrue($cron->isDue(self::at('2026-10-01 09:00')));  // jeudi 1er
        $this->assertTrue($cron->isDue(self::at('2026-09-28 09:00')));  // lundi 28
        $this->assertFalse($cron->isDue(self::at('2026-09-29 09:00'))); // mardi 29
    }

    public function test_next_run_after(): void
    {
        $this->assertSame('2026-09-24 13:38', (new CronExpression('* * * * *'))->nextRunAfter(self::at('2026-09-24 13:37:45'))->format('Y-m-d H:i'));
        $this->assertSame('2026-09-25 03:00', (new CronExpression('0 3 * * *'))->nextRunAfter(self::at('2026-09-24 03:00'))->format('Y-m-d H:i'));
        $this->assertSame('2026-09-28 08:00', (new CronExpression('0 8 * * 1'))->nextRunAfter(self::at('2026-09-24 13:37'))->format('Y-m-d H:i'));
        $this->assertSame('2027-01-01 00:00', (new CronExpression('0 0 1 1 *'))->nextRunAfter(self::at('2026-09-24 13:37'))->format('Y-m-d H:i'));
        $this->assertSame('2026-09-24 23:45', (new CronExpression('45 23 * * *'))->nextRunAfter(self::at('2026-09-24 13:37'))->format('Y-m-d H:i'));
    }

    public function test_february_29th_is_found_in_the_next_leap_year(): void
    {
        $this->assertSame('2028-02-29 00:00', (new CronExpression('0 0 29 2 *'))->nextRunAfter(self::at('2026-09-24 13:37'))->format('Y-m-d H:i'));
    }

    public function test_an_impossible_date_fails_instead_of_looping_forever(): void
    {
        $this->expectException(\RuntimeException::class);

        (new CronExpression('0 0 31 2 *'))->nextRunAfter(self::at('2026-09-24 13:37'));
    }

    /** @return iterable<array{string}> */
    public static function invalidExpressions(): iterable
    {
        yield 'trop peu de champs' => ['* * * *'];
        yield 'minute hors limites' => ['60 * * * *'];
        yield 'heure hors limites' => ['0 24 * * *'];
        yield 'mois zéro' => ['0 0 1 0 *'];
        yield 'intervalle inversé' => ['0 10-8 * * *'];
        yield 'pas nul' => ['*/0 * * * *'];
        yield 'texte' => ['tous les jours'];
    }

    /** @dataProvider invalidExpressions */
    public function test_invalid_expressions_are_rejected(string $expression): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CronExpression($expression);
    }
}
