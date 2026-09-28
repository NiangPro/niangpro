<?php

namespace Tests\Unit;

use Niang\Core\Exceptions\ConfigurationException;
use Niang\Core\Exceptions\MailException;
use Niang\Core\Mail;
use Niang\Core\Mailable;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSmtpServer;

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

    /** @param array<string, string> $env */
    private function withEnv(array $env, \Closure $callback): void
    {
        $previous = [];
        foreach ($env as $key => $value) {
            $previous[$key] = getenv($key);
            putenv("$key=$value");
        }

        try {
            $callback();
        } finally {
            foreach ($previous as $key => $value) {
                $value === false ? putenv($key) : putenv("$key=$value");
            }
        }
    }

    public function test_smtp_mailer_sends_through_the_configured_server(): void
    {
        $server = new FakeSmtpServer();

        $this->withEnv([
            'MAIL_MAILER' => 'smtp',
            'MAIL_HOST' => '127.0.0.1',
            'MAIL_PORT' => (string) $server->port,
            'MAIL_ENCRYPTION' => 'none',
            'MAIL_FROM_ADDRESS' => 'contact@niangpro.test',
            'MAIL_FROM_NAME' => 'NiangPro',
        ], fn () => Mail::to('awa@example.com')->send(new MailTestWelcomeMailable()));

        $transcript = $server->transcript();
        $this->assertStringContainsString("RCPT TO:<awa@example.com>\r\n", $transcript);
        $this->assertStringContainsString("Subject: Bienvenue\r\n", $transcript);
        $this->assertStringContainsString('From: NiangPro <contact@niangpro.test>', $transcript);
    }

    public function test_smtp_mailer_without_host_fails_loudly(): void
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('MAIL_HOST');

        $this->withEnv(['MAIL_MAILER' => 'smtp', 'MAIL_HOST' => '', 'MAIL_FROM_ADDRESS' => ''], fn () => Mail::to('a@example.com')->send(new MailTestWelcomeMailable()));
    }

    public function test_an_unknown_mailer_fails_instead_of_silently_logging(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->withEnv(['MAIL_MAILER' => 'smpt'], fn () => Mail::to('a@example.com')->send(new MailTestWelcomeMailable()));
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
