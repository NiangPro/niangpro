<?php

namespace Tests\Support;

/**
 * Lance tests/Support/fake-smtp-server.php dans un process séparé (le client SMTP bloque sur ses
 * lectures socket : le serveur ne peut pas tourner dans le même process que le test).
 */
final class FakeSmtpServer
{
    /** @var resource */
    private $process;

    /** @var array<int, resource> */
    private array $pipes;

    public readonly int $port;

    private string $transcriptPath;

    public function __construct(array $options = [])
    {
        $this->transcriptPath = tempnam(sys_get_temp_dir(), 'niang-smtp-');

        $command = [PHP_BINARY, __DIR__ . '/fake-smtp-server.php', $this->transcriptPath, json_encode($options)];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Impossible de lancer le faux serveur SMTP.');
        }

        $this->process = $process;
        $this->pipes = $pipes;

        $port = trim((string) fgets($pipes[1]));

        if (!ctype_digit($port)) {
            throw new \RuntimeException('Le faux serveur SMTP n\'a pas démarré : ' . stream_get_contents($pipes[2]));
        }

        $this->port = (int) $port;
    }

    /** Tout ce que le client a envoyé, une fois la connexion terminée (attend la fin du serveur). */
    public function transcript(): string
    {
        $this->stop();

        return (string) file_get_contents($this->transcriptPath);
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            foreach ($this->pipes as $pipe) {
                fclose($pipe);
            }

            // Le serveur s'arrête seul après QUIT ou une coupure ; on ne le tue que s'il traîne.
            $deadline = microtime(true) + 5;
            while (proc_get_status($this->process)['running'] && microtime(true) < $deadline) {
                usleep(10_000);
            }

            proc_terminate($this->process);
            proc_close($this->process);
        }
    }

    public function __destruct()
    {
        $this->stop();
        @unlink($this->transcriptPath);
    }

    /** Certificat auto-signé pour localhost/127.0.0.1 (clé + certificat dans un seul PEM). */
    public static function selfSignedCertificate(): string
    {
        // Config openssl minimale fournie explicitement : sur Windows, sans OPENSSL_CONF, les
        // fonctions openssl_csr_* échouent faute de trouver un openssl.cnf.
        $config = tempnam(sys_get_temp_dir(), 'niang-openssl-');
        file_put_contents($config, "[ req ]\ndistinguished_name = req_distinguished_name\n[ req_distinguished_name ]\n");
        $options = ['config' => $config, 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];

        $key = openssl_pkey_new($options);
        $csr = openssl_csr_new(['commonName' => '127.0.0.1'], $key, $options);
        $cert = openssl_csr_sign($csr, null, $key, 1, $options);

        if ($key === false || $csr === false || $cert === false) {
            throw new \RuntimeException('Génération du certificat de test impossible : ' . (openssl_error_string() ?: 'erreur openssl inconnue'));
        }

        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($key, $keyPem, null, $options);
        @unlink($config);

        $path = tempnam(sys_get_temp_dir(), 'niang-cert-');
        file_put_contents($path, $certPem . $keyPem);

        return $path;
    }
}
