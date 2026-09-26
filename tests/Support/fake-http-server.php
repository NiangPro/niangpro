<?php

/**
 * Récepteur HTTP minimal pour les tests (webhooks) : écoute sur un port libre, l'affiche sur la
 * sortie standard, puis répond à une seule requête avec le statut demandé et enregistre la requête
 * reçue (ligne, en-têtes, corps) dans un fichier JSON.
 *
 * Usage : php fake-http-server.php <fichier-transcript> <statut> [en-tête-location]
 */

[$script, $transcript, $status] = $argv + [2 => '200'];
$location = $argv[3] ?? null;

$server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);

if ($server === false) {
    fwrite(STDERR, "$error ($errno)\n");
    exit(1);
}

echo parse_url('tcp://' . stream_socket_get_name($server, false), PHP_URL_PORT), "\n";
fflush(STDOUT);

$client = @stream_socket_accept($server, 10);

if ($client === false) {
    exit(1);
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

file_put_contents($transcript, json_encode(['request' => $requestLine, 'headers' => $headers, 'body' => $body]));

$reply = "HTTP/1.1 $status Test\r\nContent-Type: application/json\r\nConnection: close\r\n";
if ($location !== null) {
    $reply .= "Location: $location\r\n";
}
fwrite($client, $reply . "Content-Length: 2\r\n\r\n{}");
fclose($client);
