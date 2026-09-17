<?php

namespace Tests\Unit;

use Niang\Core\Mail;
use Niang\Core\Mailable;
use PHPUnit\Framework\TestCase;

class MailTest extends TestCase
{
    protected function tearDown(): void
    {
        Mail::reset();
        parent::tearDown();
    }

    public function test_fake_collects_sent_mails_instead_of_logging(): void
    {
        Mail::fake();

        Mail::to('awa@example.com')->send(new MailTestWelcomeMailable());

        $sent = Mail::sent();
        $this->assertCount(1, $sent);
        $this->assertSame('awa@example.com', $sent[0]['to']);
        $this->assertInstanceOf(MailTestWelcomeMailable::class, $sent[0]['mailable']);
        $this->assertSame('Bienvenue', $sent[0]['mailable']->subject());
    }

    public function test_sent_is_empty_before_anything_is_sent(): void
    {
        Mail::fake();

        $this->assertSame([], Mail::sent());
    }

    public function test_reset_clears_faked_mails(): void
    {
        Mail::fake();
        Mail::to('a@example.com')->send(new MailTestWelcomeMailable());
        $this->assertCount(1, Mail::sent());

        Mail::reset();

        $this->assertSame([], Mail::sent());
    }

    public function test_default_log_driver_writes_to_the_log_file_without_throwing(): void
    {
        // Pas de fake() ici : passe par la branche 'log' (voir MailTestWelcomeMailable), qui délègue
        // à Log::info() — déjà couvert par LoggerTest, on vérifie juste qu'aucune exception ne fuit.
        Mail::to('a@example.com')->send(new MailTestWelcomeMailable());

        $this->assertSame([], Mail::sent());
    }
}

class MailTestWelcomeMailable extends Mailable
{
    public function subject(): string
    {
        return 'Bienvenue';
    }

    public function body(): string
    {
        return 'Bienvenue sur NiangPro !';
    }
}
