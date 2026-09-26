<?php

namespace Tests\Feature;

use Niang\Core\Contracts\NotificationChannel;
use Niang\Core\Contracts\ShouldQueue;
use Niang\Core\Exceptions\NotificationException;
use Niang\Core\Mail;
use Niang\Core\Mailable;
use Niang\Core\Notification;
use Niang\Core\Queue;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;
use Tests\Support\FakeHttpServer;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private array $awa = ['id' => 1, 'name' => 'Awa', 'email' => 'awa@example.test'];
    private array $modou = ['id' => 2, 'name' => 'Modou', 'email' => 'modou@example.test'];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        NotificationTestSmsChannel::$sent = [];
    }

    protected function tearDown(): void
    {
        Mail::reset();
        Queue::reset();
        parent::tearDown();
    }

    public function test_mail_channel_sends_to_mail_to_the_email_column(): void
    {
        Notification::send($this->awa, new NotificationTestShipped(['mail']));

        $this->assertCount(1, Mail::sent());
        $this->assertSame('awa@example.test', Mail::sent()[0]['to']);
        $this->assertSame('Commande 42 expédiée', Mail::sent()[0]['mailable']->subject());
    }

    public function test_database_channel_stores_and_reads_back_per_recipient(): void
    {
        Notification::send([$this->awa, $this->modou], new NotificationTestShipped(['database']));
        Notification::send($this->awa, new NotificationTestShipped(['database'], 43));

        $mine = Notification::for($this->awa);
        $this->assertCount(2, $mine);
        $this->assertSame(43, $mine[0]['data']['order'], 'la plus récente d\'abord');
        $this->assertSame(NotificationTestShipped::class, $mine[0]['type']);
        $this->assertNull($mine[0]['read_at']);
        $this->assertCount(1, Notification::for($this->modou));
        $this->assertSame(2, Notification::unreadCount($this->awa));
    }

    public function test_mark_as_read_is_limited_to_the_recipient(): void
    {
        Notification::send([$this->awa, $this->modou], new NotificationTestShipped(['database']));
        $awaId = Notification::for($this->awa)[0]['id'];

        $this->assertFalse(Notification::markAsRead($this->modou, $awaId), 'Modou ne peut pas marquer la notification d\'Awa');
        $this->assertSame(1, Notification::unreadCount($this->awa));

        $this->assertTrue(Notification::markAsRead($this->awa, $awaId));
        $this->assertFalse(Notification::markAsRead($this->awa, $awaId), 'déjà lue');
        $this->assertSame(0, Notification::unreadCount($this->awa));
        $this->assertSame([], Notification::unread($this->awa));
        $this->assertSame(1, Notification::unreadCount($this->modou));

        $this->assertSame(1, Notification::markAllAsRead($this->modou));
        $this->assertSame(0, Notification::unreadCount($this->modou));
    }

    public function test_several_channels_and_a_custom_channel(): void
    {
        Notification::send($this->awa, new NotificationTestShipped(['mail', 'database', NotificationTestSmsChannel::class]));

        $this->assertCount(1, Mail::sent());
        $this->assertCount(1, Notification::for($this->awa));
        $this->assertSame(['awa@example.test' => 'Commande 42 expédiée'], NotificationTestSmsChannel::$sent);
    }

    public function test_an_unknown_channel_or_a_missing_method_fails_clearly(): void
    {
        try {
            Notification::send($this->awa, new NotificationTestShipped(['pigeon']));
            $this->fail('canal inconnu accepté');
        } catch (NotificationException $e) {
            $this->assertStringContainsString('pigeon', $e->getMessage());
        }

        $this->expectException(NotificationException::class);
        $this->expectExceptionMessage('webhookUrl()');
        Notification::send($this->awa, new NotificationTestShipped(['webhook']));
    }

    public function test_should_queue_notifications_go_through_the_queue(): void
    {
        Queue::reset();

        Notification::send([$this->awa, $this->modou], new NotificationTestQueued(['mail']));

        $this->assertSame([], Mail::sent());
        $this->assertSame(2, Queue::pending(), 'un job par destinataire');

        Queue::work();

        $this->assertSame(['awa@example.test', 'modou@example.test'], array_column(Mail::sent(), 'to'));
    }

    public function test_send_now_skips_the_queue(): void
    {
        Queue::reset();

        Notification::sendNow($this->awa, new NotificationTestQueued(['mail']));

        $this->assertCount(1, Mail::sent());
        $this->assertSame(0, Queue::pending());
    }

    public function test_fake_records_instead_of_sending(): void
    {
        Notification::fake();

        Notification::send($this->awa, new NotificationTestQueued(['mail', 'database']));

        $this->assertSame([], Mail::sent());
        $this->assertSame([], Notification::for($this->awa));
        $this->assertCount(1, Notification::sent());
        $this->assertSame(['mail', 'database'], Notification::sent()[0]['channels']);
        $this->assertSame('awa@example.test', Notification::sent()[0]['notifiable']['email']);
    }

    public function test_webhook_posts_signed_json(): void
    {
        $server = new FakeHttpServer(200);

        Notification::send($this->awa, new NotificationTestWebhook($server->url(), 'secret-partagé'));

        $request = $server->received();
        $this->assertSame('POST /webhook HTTP/1.1', $request['request']);
        $this->assertSame('application/json', $request['headers']['content-type']);
        $this->assertSame(['event' => 'order.shipped', 'order' => 42, 'client' => 'Awa'], json_decode($request['body'], true));
        $this->assertSame('sha256=' . hash_hmac('sha256', $request['body'], 'secret-partagé'), $request['headers']['x-niang-signature']);
    }

    public function test_webhook_without_secret_is_not_signed(): void
    {
        $server = new FakeHttpServer(204);

        Notification::send($this->awa, new NotificationTestWebhook($server->url(), null));

        $this->assertArrayNotHasKey('x-niang-signature', $server->received()['headers']);
    }

    public function test_webhook_error_status_raises(): void
    {
        $server = new FakeHttpServer(500);

        try {
            Notification::send($this->awa, new NotificationTestWebhook($server->url(), null));
            $this->fail('une réponse 500 doit lever une erreur');
        } catch (NotificationException $e) {
            $this->assertStringContainsString('500', $e->getMessage());
        }
    }

    public function test_webhook_does_not_follow_redirects(): void
    {
        $server = new FakeHttpServer(302, 'http://169.254.169.254/latest/meta-data');

        $this->expectException(NotificationException::class);
        $this->expectExceptionMessage('302');
        Notification::send($this->awa, new NotificationTestWebhook($server->url(), null));
    }

    public function test_webhook_only_accepts_http_urls(): void
    {
        foreach (['file:///etc/passwd', 'ftp://example.test/x', 'pas une url'] as $url) {
            try {
                Notification::send($this->awa, new NotificationTestWebhook($url, null));
                $this->fail("URL acceptée : $url");
            } catch (NotificationException $e) {
                $this->assertStringContainsString('URL invalide', $e->getMessage());
            }
        }
    }
}

class NotificationTestShipped extends Notification
{
    /** @param list<string> $channels */
    public function __construct(private array $channels, private int $order = 42)
    {
    }

    public function via(array $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(array $notifiable): Mailable
    {
        return new NotificationTestMailable("Commande {$this->order} expédiée");
    }

    public function toDatabase(array $notifiable): array
    {
        return ['order' => $this->order];
    }
}

class NotificationTestQueued extends NotificationTestShipped implements ShouldQueue
{
}

class NotificationTestWebhook extends Notification
{
    public function __construct(private string $url, private ?string $secret)
    {
    }

    public function via(array $notifiable): array
    {
        return ['webhook'];
    }

    public function toWebhook(array $notifiable): array
    {
        return ['event' => 'order.shipped', 'order' => 42, 'client' => $notifiable['name']];
    }

    public function webhookUrl(array $notifiable): string
    {
        return $this->url;
    }

    public function webhookSecret(): ?string
    {
        return $this->secret;
    }
}

class NotificationTestMailable extends Mailable
{
    public function __construct(private string $line)
    {
    }

    public function subject(): string
    {
        return $this->line;
    }

    public function body(): string
    {
        return $this->line;
    }
}

class NotificationTestSmsChannel implements NotificationChannel
{
    /** @var array<string, string> */
    public static array $sent = [];

    public function send(array $notifiable, Notification $notification): void
    {
        self::$sent[$notifiable['email']] = $notification->toMail($notifiable)->subject();
    }
}
