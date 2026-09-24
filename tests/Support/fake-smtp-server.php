<?php

/**
 * Faux serveur SMTP pour les tests de Niang\Core\SmtpTransport, lancé dans un process séparé par
 * Tests\Support\FakeSmtpServer. Il écoute sur un port libre (affiché sur la première ligne de
 * stdout), accepte UNE connexion, joue le protocole comme un vrai serveur (EHLO multi-lignes,
 * STARTTLS avec un vrai certificat, AUTH PLAIN/LOGIN, DATA terminé par « . ») et écrit dans le
 * fichier de transcription chaque ligne reçue du client, telle quelle.
 *
 * Usage : php fake-smtp-server.php <transcription> '<options JSON>'
 * Options : cert (chemin PEM : active STARTTLS, ou TLS implicite avec implicit_tls), implicit_tls,
 *           auth (['user' => ..., 'password' => ...]), mechanisms (défaut ['PLAIN', 'LOGIN']),
 *           reject_rcpt (bool).
 */

[, $transcriptPath, $optionsJson] = $argv + [null, null, '{}'];
$options = json_decode((string) $optionsJson, true) ?: [];

$context = stream_context_create(isset($options['cert']) ? ['ssl' => ['local_cert' => $options['cert']]] : []);
$scheme = !empty($options['implicit_tls']) ? 'ssl' : 'tcp';
$server = stream_socket_server("$scheme://127.0.0.1:0", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

if ($server === false) {
    fwrite(STDERR, "listen failed: $errstr\n");
    exit(1);
}

echo parse_url('tcp://' . stream_socket_get_name($server, false), PHP_URL_PORT) . "\n";
flush();

$client = @stream_socket_accept($server, 10);

if ($client === false) {
    exit(1);
}

$log = fopen($transcriptPath, 'w');
$send = static function (string $line) use ($client): void {
    fwrite($client, $line . "\r\n");
};

$tls = !empty($options['implicit_tls']);
$authenticated = false;
$mechanisms = $options['mechanisms'] ?? ['PLAIN', 'LOGIN'];
$expected = $options['auth'] ?? null;

$send('220 fake.smtp.test ESMTP prêt');

while (($line = fgets($client)) !== false) {
    fwrite($log, $line);
    $command = strtoupper(trim($line));

    if (str_starts_with($command, 'EHLO')) {
        $extensions = ['SIZE 10485760'];
        if (isset($options['cert']) && !$tls) {
            $extensions[] = 'STARTTLS';
        }
        if ($expected !== null && $mechanisms !== []) {
            $extensions[] = 'AUTH ' . implode(' ', $mechanisms);
        }
        $send('250-fake.smtp.test bonjour');
        foreach ($extensions as $i => $extension) {
            $send(($i === count($extensions) - 1 ? '250 ' : '250-') . $extension);
        }
    } elseif ($command === 'STARTTLS') {
        $send('220 On passe en TLS');
        $tls = stream_socket_enable_crypto($client, true, STREAM_CRYPTO_METHOD_TLS_SERVER);
        fwrite($log, $tls ? "[TLS OK]\n" : "[TLS ÉCHEC]\n");
        if (!$tls) {
            break;
        }
    } elseif (str_starts_with($command, 'AUTH PLAIN ')) {
        [, $user, $password] = explode("\0", base64_decode(substr(trim($line), 11))) + [null, null, null];
        $authenticated = $expected !== null && $user === $expected['user'] && $password === $expected['password'];
        $send($authenticated ? '235 Authentifié' : '535 Identifiants refusés');
    } elseif ($command === 'AUTH LOGIN') {
        $send('334 VXNlcm5hbWU6');
        $user = base64_decode(trim((string) fgets($client)));
        fwrite($log, "[login user: $user]\n");
        $send('334 UGFzc3dvcmQ6');
        $password = base64_decode(trim((string) fgets($client)));
        $authenticated = $expected !== null && $user === $expected['user'] && $password === $expected['password'];
        $send($authenticated ? '235 Authentifié' : '535 Identifiants refusés');
    } elseif (str_starts_with($command, 'MAIL FROM:')) {
        $send($expected !== null && !$authenticated ? '530 Authentification requise' : '250 OK');
    } elseif (str_starts_with($command, 'RCPT TO:')) {
        $send(!empty($options['reject_rcpt']) ? '550 Boîte inconnue' : '250 OK');
    } elseif ($command === 'DATA') {
        $send('354 Terminez par <CRLF>.<CRLF>');
        while (($dataLine = fgets($client)) !== false) {
            fwrite($log, $dataLine);
            if ($dataLine === ".\r\n") {
                break;
            }
        }
        $send('250 Message accepté');
    } elseif ($command === 'QUIT') {
        $send('221 Au revoir');
        break;
    } else {
        $send('502 Commande inconnue');
    }
}

fclose($log);
fclose($client);
