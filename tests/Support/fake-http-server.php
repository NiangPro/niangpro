<?php

/**
 * Serveur HTTP minimal pour les tests (webhooks, OAuth) : écoute sur un port libre, l'affiche sur
 * la sortie standard, puis répond dans l'ordre aux réponses scriptées (une par connexion) et
 * enregistre chaque requête reçue (ligne, en-têtes, corps) dans un fichier JSON.
 *
 * Usage : php fake-http-server.php <fichier-transcript> <fichier-réponses.json>
 *         réponses : [{"status": 200, "headers": {"Location": "..."}, "body": "..."}, ...]
 */

[$script, $transcript, $responsesFile] = $argv;
$responses = json_decode((string) file_get_contents($responsesFile), true);
$received = [];

$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);

if ($server === false) {
    fwrite(STDERR, "$error ($errno)\n");
    exit(1);
}

echo parse_url('tcp://' . stream_socket_get_name($server, false), PHP_URL_PORT), "\n";
fflush(STDOUT);

foreach ($responses as $response) {
    $client = @stream_socket_accept($server, 10);

    if ($client === false) {
        break;
    }

    $head = '';
    while (!str_contains($head, "\r\n\r\n") && ($chunk = fread($client, 1)) !== false && $chunk !== '') {
        $head .= $chunk;
    }

    [$head] = explode("\r\n\r\n", $head, 2);
    $lines = explode("\r\n", $head);
    $requestLine = array_shift($lines);
    $headers = [];

    foreach ($lines as $line) {
        [$name, $value] = array_pad(explode(':', $line, 2), 2, '');
        $headers[strtolower(trim($name))] = trim($value);
    }

    $body = '';
    $length = (int) ($headers['content-length'] ?? 0);
    while (strlen($body) < $length && ($chunk = fread($client, $length - strlen($body))) !== false && $chunk !== '') {
        $body .= $chunk;
    }

    $received[] = ['request' => $requestLine, 'headers' => $headers, 'body' => $body];
    file_put_contents($transcript, json_encode($received));

    $replyBody = (string) ($response['body'] ?? '{}');
    $reply = "HTTP/1.1 {$response['status']} Test\r\nContent-Type: application/json\r\nConnection: close\r\n";
    foreach ($response['headers'] ?? [] as $name => $value) {
        $reply .= "$name: $value\r\n";
    }
    fwrite($client, $reply . 'Content-Length: ' . strlen($replyBody) . "\r\n\r\n" . $replyBody);
    fclose($client);
}
