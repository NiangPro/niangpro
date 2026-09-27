<?php

namespace Tests\Unit;

use Niang\Core\Exceptions\MailException;
use Niang\Core\Mailable;
use Niang\Core\MailAttachment;
use Niang\Core\SmtpTransport;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSmtpServer;

/**
 * Contre un vrai serveur (process séparé, vraie socket, vrai TLS) : tests/Support/fake-smtp-server.php.
 */
class SmtpTransportTest extends TestCase
{
    private static ?string $certificate = null;

    public static function tearDownAfterClass(): void
    {
        if (self::$certificate !== null) {
            @unlink(self::$certificate);
        }
    }

    private static function certificate(): string
    {
        return self::$certificate ??= FakeSmtpServer::selfSignedCertificate();
    }

    /** @param array{host?: string, port?: int, encryption?: string, username?: ?string, password?: ?string, from_address?: string, from_name?: ?string, timeout?: int, ehlo_domain?: string, stream_options?: array<string, mixed>} $overrides */
    private function transport(FakeSmtpServer $server, array $overrides = []): SmtpTransport
    {
        return new SmtpTransport([
            'host' => '127.0.0.1',
            'port' => $server->port,
            'encryption' => 'none',
            'username' => null,
            'password' => null,
            'from_address' => 'contact@niangpro.test',
            'from_name' => 'NiangPro',
            'timeout' => 5,
            'ehlo_domain' => 'app.niangpro.test',
            ...$overrides,
        ]);
    }

    /** Accepte le certificat auto-signé du faux serveur — jamais le comportement par défaut. */
    private static function trustSelfSigned(): array
    {
        return ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
    }

    public function test_it_sends_a_plain_text_message_through_the_whole_smtp_dialogue(): void
    {
        $server = new FakeSmtpServer();

        $this->transport($server)->send('awa@example.test', new SmtpTestMailable('Bienvenue', "Bonjour Awa,\nvotre compte est prêt."));

        $transcript = $server->transcript();
        $this->assertStringContainsString("EHLO app.niangpro.test\r\n", $transcript);
        $this->assertStringContainsString("MAIL FROM:<contact@niangpro.test>\r\n", $transcript);
        $this->assertStringContainsString("RCPT TO:<awa@example.test>\r\n", $transcript);
        $this->assertStringContainsString("DATA\r\n", $transcript);
        $this->assertStringContainsString("Subject: Bienvenue\r\n", $transcript);
        $this->assertStringContainsString("Content-Type: text/plain; charset=UTF-8\r\n", $transcript);
        $this->assertStringContainsString('pr=C3=AAt.', $transcript); // « prêt » en quoted-printable
        $this->assertStringContainsString("\r\n.\r\nQUIT\r\n", $transcript);
    }

    public function test_it_upgrades_to_tls_with_starttls_before_authenticating(): void
    {
        $server = new FakeSmtpServer(['cert' => self::certificate(), 'auth' => ['user' => 'awa', 'password' => 's3cret']]);

        $this->transport($server, [
            'encryption' => 'tls',
            'username' => 'awa',
            'password' => 's3cret',
            'stream_options' => self::trustSelfSigned(),
        ])->send('moussa@example.test', new SmtpTestMailable('Test', 'Corps'));

        $transcript = $server->transcript();
        $tlsAt = strpos($transcript, '[TLS OK]');
        $authAt = strpos($transcript, 'AUTH PLAIN ');

        $this->assertNotFalse($tlsAt, $transcript);
        $this->assertNotFalse($authAt, $transcript);
        $this->assertLessThan($authAt, $tlsAt, 'AUTH doit être envoyé après la négociation TLS, jamais avant.');
        $this->assertSame(2, substr_count($transcript, 'EHLO'), 'EHLO doit être renvoyé après STARTTLS (RFC 3207).');
        $this->assertStringContainsString("\r\n.\r\n", $transcript);
    }

    public function test_it_supports_implicit_tls(): void
    {
        $server = new FakeSmtpServer(['cert' => self::certificate(), 'implicit_tls' => true]);

        $this->transport($server, ['encryption' => 'ssl', 'stream_options' => self::trustSelfSigned()])
            ->send('awa@example.test', new SmtpTestMailable('Test', 'Corps'));

        $this->assertStringContainsString("QUIT\r\n", $server->transcript());
    }

    public function test_it_verifies_the_server_certificate_by_default(): void
    {
        $server = new FakeSmtpServer(['cert' => self::certificate()]);

        try {
            $this->transport($server, ['encryption' => 'tls'])->send('awa@example.test', new SmtpTestMailable('Test', 'Corps'));
            $this->fail('Un certificat auto-signé ne doit pas être accepté sans configuration explicite.');
        } catch (MailException $e) {
            $this->assertStringContainsString('TLS', $e->getMessage());
        }

        $this->assertStringNotContainsString('MAIL FROM', $server->transcript());
    }

    public function test_it_refuses_to_continue_in_clear_text_when_starttls_is_not_offered(): void
    {
        $server = new FakeSmtpServer(); // pas de certificat : pas de STARTTLS annoncé

        try {
            $this->transport($server, ['encryption' => 'tls'])->send('awa@example.test', new SmtpTestMailable('Test', 'Corps'));
            $this->fail('L\'envoi aurait dû être interrompu.');
        } catch (MailException $e) {
            $this->assertStringContainsString('STARTTLS', $e->getMessage());
        }

        $this->assertStringNotContainsString('MAIL FROM', $server->transcript());
    }

    public function test_it_never_sends_credentials_in_clear_text_to_a_remote_host(): void
    {
        $transport = new SmtpTransport([
            'host' => 'smtp.example.test',
            'port' => 25,
            'encryption' => 'none',
            'username' => 'awa',
            'password' => 's3cret',
            'from_address' => 'contact@niangpro.test',
            'from_name' => null,
            'timeout' => 1,
            'ehlo_domain' => 'localhost',
        ]);

        // Le refus ne demande pas de réseau : on le vérifie sur la méthode privée directement.
        $method = new \ReflectionMethod($transport, 'authenticate');

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('non chiffrée');
        $method->invoke($transport);
    }

    public function test_it_uses_auth_login_when_plain_is_not_offered(): void
    {
        $server = new FakeSmtpServer(['auth' => ['user' => 'awa', 'password' => 's3cret'], 'mechanisms' => ['LOGIN']]);

        $this->transport($server, ['username' => 'awa', 'password' => 's3cret'])
            ->send('moussa@example.test', new SmtpTestMailable('Test', 'Corps'));

        $transcript = $server->transcript();
        $this->assertStringContainsString("AUTH LOGIN\r\n", $transcript);
        $this->assertStringContainsString('[login user: awa]', $transcript);
        $this->assertStringContainsString("QUIT\r\n", $transcript);
    }

    public function test_rejected_credentials_raise_an_error_without_leaking_the_password(): void
    {
        $server = new FakeSmtpServer(['auth' => ['user' => 'awa', 'password' => 'le-bon']]);

        try {
            $this->transport($server, ['username' => 'awa', 'password' => 'mauvais-mdp'])
                ->send('moussa@example.test', new SmtpTestMailable('Test', 'Corps'));
            $this->fail('Des identifiants refusés doivent lever une exception.');
        } catch (MailException $e) {
            $this->assertStringContainsString('535', $e->getMessage());
            $this->assertStringNotContainsString('mauvais-mdp', $e->getMessage());
            $this->assertStringNotContainsString(base64_encode("\0awa\0mauvais-mdp"), $e->getMessage());
        }
    }

    public function test_a_rejected_recipient_raises_an_error(): void
    {
        $server = new FakeSmtpServer(['reject_rcpt' => true]);

        $this->expectException(MailException::class);
        $this->expectExceptionMessage('550');

        $this->transport($server)->send('inconnu@example.test', new SmtpTestMailable('Test', 'Corps'));
    }

    public function test_an_unreachable_server_raises_a_clear_error(): void
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Connexion SMTP impossible');

        // Port 1 : rien n'écoute là-dessus sur une machine de test.
        (new SmtpTransport([
            'host' => '127.0.0.1', 'port' => 1, 'encryption' => 'none', 'username' => null, 'password' => null,
            'from_address' => 'contact@niangpro.test', 'from_name' => null, 'timeout' => 2, 'ehlo_domain' => 'localhost',
        ]))->send('awa@example.test', new SmtpTestMailable('Test', 'Corps'));
    }

    public function test_lines_starting_with_a_dot_are_escaped_so_the_message_is_not_cut(): void
    {
        $server = new FakeSmtpServer();

        $this->transport($server)->send('awa@example.test', new SmtpTestMailable('Test', "Début\n.\nFin après le point"));

        $transcript = $server->transcript();
        $this->assertStringContainsString("\r\n..\r\n", $transcript);
        $this->assertStringContainsString('Fin apr=C3=A8s le point', $transcript);
    }

    public function test_html_mailables_are_sent_as_multipart_alternative(): void
    {
        $server = new FakeSmtpServer();

        $this->transport($server)->send('awa@example.test', new SmtpTestMailable('Test', 'Version texte', '<p>Version <b>HTML</b></p>'));

        $transcript = $server->transcript();
        $this->assertStringContainsString('Content-Type: multipart/alternative; boundary="niang-', $transcript);
        $this->assertStringContainsString("Content-Type: text/plain; charset=UTF-8\r\n", $transcript);
        $this->assertStringContainsString("Content-Type: text/html; charset=UTF-8\r\n", $transcript);
        $this->assertStringContainsString('<p>Version <b>HTML</b></p>', $transcript);
    }

    public function test_non_ascii_subjects_and_sender_names_are_encoded(): void
    {
        $transport = $this->transportWithoutServer(['from_name' => 'Équipe NiangPro']);

        $message = $transport->buildMessage('awa@example.test', new SmtpTestMailable('Réinitialisation du mot de passe', 'Corps'));

        $this->assertStringContainsString('Subject: =?UTF-8?B?' . base64_encode('Réinitialisation du mot de passe') . '?=', $message);
        $this->assertStringContainsString('From: =?UTF-8?B?' . base64_encode('Équipe NiangPro') . '?= <contact@niangpro.test>', $message);
    }

    public function test_header_injection_through_the_subject_is_rejected(): void
    {
        $this->expectException(MailException::class);

        $this->transportWithoutServer()->buildMessage('awa@example.test', new SmtpTestMailable("Sujet\r\nBcc: victime@example.test", 'Corps'));
    }

    public function test_header_injection_through_the_recipient_is_rejected(): void
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Adresse email invalide');

        $this->transportWithoutServer()->send("awa@example.test>\r\nBcc: <victime@example.test", new SmtpTestMailable('Test', 'Corps'));
    }

    public function test_cc_and_bcc_receive_the_message_but_bcc_never_appears_in_it(): void
    {
        $server = new FakeSmtpServer();

        $this->transport($server)->send(
            'awa@example.test',
            new SmtpTestMailable('Réunion', 'Corps'),
            ['modou@example.test', 'awa@example.test'],
            ['archives@example.test']
        );

        $transcript = $server->transcript();
        $this->assertSame(1, substr_count($transcript, 'RCPT TO:<awa@example.test>'), 'une adresse en double ne reçoit qu\'un exemplaire');
        $this->assertStringContainsString("RCPT TO:<modou@example.test>\r\n", $transcript);
        $this->assertStringContainsString("RCPT TO:<archives@example.test>\r\n", $transcript);
        $this->assertStringContainsString("Cc: <modou@example.test>, <awa@example.test>\r\n", $transcript);

        $message = substr($transcript, (int) strpos($transcript, "DATA\r\n"));
        $this->assertStringNotContainsString('archives@example.test', $message);
        // Pas de recherche de « bcc » dans tout le message : le Message-ID aléatoire (hexadécimal) le contient parfois.
        $this->assertDoesNotMatchRegularExpression('/^bcc:/im', $message);
    }

    public function test_attachments_are_sent_as_multipart_mixed_and_decode_back_to_the_same_bytes(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'np-pj');
        $bytes = random_bytes(3000) . "\r\n.\r\n";
        file_put_contents($file, $bytes);

        $server = new FakeSmtpServer();

        try {
            $this->transport($server)->send('awa@example.test', new SmtpTestMailable('Votre facture', 'Ci-joint.', '<p>Ci-joint.</p>', [
                MailAttachment::fromPath($file, 'Facture été 2026.pdf', 'application/pdf'),
                MailAttachment::fromData("id;total\n1;100\n", 'export.csv', 'text/csv'),
            ]));
        } finally {
            unlink($file);
        }

        $transcript = $server->transcript();
        $this->assertStringContainsString('Content-Type: multipart/mixed; boundary="niang-mixed-', $transcript);
        $this->assertStringContainsString('Content-Type: multipart/alternative; boundary="niang-', $transcript);
        $this->assertStringContainsString("Content-Disposition: attachment; filename*=UTF-8''Facture%20%C3%A9t%C3%A9%202026.pdf\r\n", $transcript);
        $this->assertStringContainsString("Content-Disposition: attachment; filename=\"export.csv\"\r\n", $transcript);
        $this->assertStringContainsString('Content-Type: text/csv; name="export.csv"', $transcript);

        preg_match('/filename\*=UTF-8\'\'Facture[^\r]*\r\n\r\n(.*?)\r\n--niang-mixed-/s', $transcript, $matches);
        $this->assertSame($bytes, base64_decode(str_replace("\r\n", '', $matches[1] ?? ''), true));

        foreach (explode("\r\n", $matches[1] ?? '') as $line) {
            $this->assertLessThanOrEqual(76, strlen($line));
        }
    }

    public function test_a_missing_attachment_file_fails_before_connecting(): void
    {
        $this->expectException(MailException::class);
        $this->expectExceptionMessage('Pièce jointe introuvable');

        $this->transportWithoutServer()->send('awa@example.test', new SmtpTestMailable('Test', 'Corps', null, [MailAttachment::fromPath('/nulle/part/facture.pdf')]));
    }

    public function test_header_injection_through_cc_or_bcc_is_rejected(): void
    {
        foreach ([[["x@example.test>\r\nBcc: <victime@example.test"], []], [[], ["x@example.test\r\nSubject: pirate"]]] as [$cc, $bcc]) {
            try {
                $this->transportWithoutServer()->send('awa@example.test', new SmtpTestMailable('Test', 'Corps'), $cc, $bcc);
                $this->fail('adresse acceptée');
            } catch (MailException $e) {
                $this->assertStringContainsString('Adresse email invalide', $e->getMessage());
            }
        }
    }

    public function test_an_invalid_encryption_value_is_rejected(): void
    {
        $this->expectException(MailException::class);

        $this->transportWithoutServer(['encryption' => 'starttls']);
    }

    /** @param array{host?: string, port?: int, encryption?: string, username?: ?string, password?: ?string, from_address?: string, from_name?: ?string, timeout?: int, ehlo_domain?: string, stream_options?: array<string, mixed>} $overrides */
    private function transportWithoutServer(array $overrides = []): SmtpTransport
    {
        return new SmtpTransport([
            'host' => '127.0.0.1', 'port' => 1, 'encryption' => 'none', 'username' => null, 'password' => null,
            'from_address' => 'contact@niangpro.test', 'from_name' => null, 'timeout' => 1, 'ehlo_domain' => 'localhost',
            ...$overrides,
        ]);
    }
}

class SmtpTestMailable extends Mailable
{
    /** @param list<MailAttachment> $files */
    public function __construct(private string $subjectLine, private string $text, private ?string $htmlBody = null, private array $files = [])
    {
    }

    public function attachments(): array
    {
        return $this->files;
    }

    public function subject(): string
    {
        return $this->subjectLine;
    }

    public function body(): string
    {
        return $this->text;
    }

    public function html(): ?string
    {
        return $this->htmlBody;
    }
}
