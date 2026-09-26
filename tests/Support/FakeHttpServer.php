<?php

namespace Tests\Support;

/** Lance tests/Support/fake-http-server.php dans un process séparé (une requête, puis il s'arrête). */
final class FakeHttpServer
{
    /** @var resource */
    private $process;

    /** @var array<int, resource> */
    private array $pipes;

    public readonly int $port;

    private string $transcriptPath;

    public function __construct(int $status = 200, ?string $location = null)
    {
        $this->transcriptPath = tempnam(sys_get_temp_dir(), 'niang-http-');

        $command = [PHP_BINARY, __DIR__ . '/fake-http-server.php', $this->transcriptPath, (string) $status];
        if ($location !== null) {
            $command[] = $location;
        }

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

    /** @return array{request: string, headers: array<string, string>, body: string}|null null si aucune requête reçue */
    public function received(): ?array
    {
        $this->stop();
        $content = (string) file_get_contents($this->transcriptPath);

        return $content === '' ? null : json_decode($content, true);
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
    }
}
