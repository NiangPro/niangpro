<?php

namespace Tests\Support;

/**
 * Lance tests/Support/fake-http-server.php dans un process séparé. Par défaut une seule réponse
 * ($status, $location) ; $responses pour enchaîner plusieurs réponses (une par requête, dans l'ordre).
 */
final class FakeHttpServer
{
    /** @var resource */
    private $process;

    /** @var array<int, resource> */
    private array $pipes;

    public readonly int $port;

    private string $transcriptPath;
    private string $responsesPath;

    /** @param list<array{status: int, headers?: array<string, string>, body?: string}> $responses */
    public function __construct(int $status = 200, ?string $location = null, array $responses = [])
    {
        $this->transcriptPath = tempnam(sys_get_temp_dir(), 'niang-http-');
        $this->responsesPath = tempnam(sys_get_temp_dir(), 'niang-http-responses-');
        $responses = $responses !== [] ? $responses : [['status' => $status, 'headers' => $location !== null ? ['Location' => $location] : []]];
        file_put_contents($this->responsesPath, json_encode($responses));

        $command = [PHP_BINARY, __DIR__ . '/fake-http-server.php', $this->transcriptPath, $this->responsesPath];

        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Impossible de lancer le faux serveur HTTP.');
        }

        $this->process = $process;
        $this->pipes = $pipes;
        $port = trim((string) fgets($pipes[1]));

        if (!ctype_digit($port)) {
            throw new \RuntimeException('Le faux serveur HTTP n\'a pas démarré : ' . stream_get_contents($pipes[2]));
        }

        $this->port = (int) $port;
    }

    public function url(string $path = '/webhook'): string
    {
        return "http://127.0.0.1:{$this->port}$path";
    }

    /** @return array{request: string, headers: array<string, string>, body: string}|null la première requête reçue */
    public function received(): ?array
    {
        return $this->all()[0] ?? null;
    }

    /** @return list<array{request: string, headers: array<string, string>, body: string}> */
    public function all(): array
    {
        $this->stop();
        $content = (string) file_get_contents($this->transcriptPath);

        return $content === '' ? [] : json_decode($content, true);
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            foreach ($this->pipes as $pipe) {
                fclose($pipe);
            }

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
        @unlink($this->responsesPath);
    }
}
