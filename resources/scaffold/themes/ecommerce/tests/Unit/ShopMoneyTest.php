<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class ShopMoneyTest extends TestCase
{
    public function test_amounts_in_cents_are_formatted_in_euros_with_a_comma(): void
    {
        $this->assertSame("24,00\u{00A0}€", Money::format(2400));
        $this->assertSame("4,90\u{00A0}€", Money::format(490));
        $this->assertSame("0,00\u{00A0}€", Money::format(0));
    }

    public function test_thousands_are_separated_by_a_narrow_no_break_space(): void
    {
        $this->assertSame("1\u{202F}299,50\u{00A0}€", Money::format(129950));
    }
}
