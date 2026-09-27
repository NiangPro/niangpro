<?php

namespace Tests\Unit;

use Niang\Core\Config;
use Niang\Core\Http\Client;
use Niang\Core\Http\Response;
use Niang\Core\Job;
use Niang\Core\Log;
use Niang\Core\Queue;
use Niang\Core\Testing\TestCase;
use Niang\Core\Trace;

class ObservabilityTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = base_path('storage/logs/' . date('Y-m-d') . '.log');
        Config::set('logging.channel', 'daily');
        Config::set('logging.level', 'debug');

        $this->app->router->get('/np-trace', function (): Response {
            Log::info('np-trace dans la requête', ['commande' => 42]);

            return Response::html('ok');
        });
    }

    /** @return list<string> lignes écrites pendant $write */
    private function logged(\Closure $write): array
    {
        $offset = is_file($this->logFile) ? (int) filesize($this->logFile) : 0;
        $write();
        clearstatcache();
        $written = is_file($this->logFile) ? substr((string) file_get_contents($this->logFile), $offset) : '';

        return array_values(array_filter(explode("\n", $written)));
    }

    public function test_every_response_carries_a_request_id_also_written_in_the_logs(): void
    {
        $lines = $this->logged(function () use (&$response) {
            $response = $this->get('/np-trace');
        });
        $id = $response->header('X-Request-Id');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $id);
        $this->assertStringContainsString('"request_id":"' . $id . '"', implode("\n", $lines));
    }

    public function test_a_request_id_from_the_load_balancer_is_kept(): void
    {
        $response = $this->get('/np-trace', ['x-request-id' => 'lb-7f3a9c21']);

        $this->assertSame('lb-7f3a9c21', $response->header('X-Request-Id'));
    }

    public function test_an_unreasonable_request_id_is_replaced(): void
    {
        foreach (["abc\r\nSet-Cookie: x=1", str_repeat('a', 200), 'court', '<script>'] as $forged) {
            $id = $this->get('/np-trace', ['X-Request-Id' => $forged])->header('X-Request-Id');

            $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', (string) $id);
        }
    }

    public function test_an_incoming_traceparent_is_joined_and_propagated(): void
    {
        $this->get('/np-trace', ['traceparent' => '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01']);

        $this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', Trace::traceId());
        $this->assertMatchesRegularExpression('/^00-4bf92f3577b34da6a3ce929d0e0e4736-[0-9a-f]{16}-01$/', Trace::traceparent());
        $this->assertNotSame('00f067aa0ba902b7', Trace::spanId(), 'nouvelle opération, parent = celle de l\'appelant');
    }

    public function test_an_invalid_traceparent_starts_a_new_trace(): void
    {
        foreach (['00-00000000000000000000000000000000-00f067aa0ba902b7-01', 'ff-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01', 'nimporte-quoi'] as $invalid) {
            $this->get('/np-trace', ['traceparent' => $invalid]);

            $this->assertNotSame('4bf92f3577b34da6a3ce929d0e0e4736', Trace::traceId());
            $this->assertNotSame(str_repeat('0', 32), Trace::traceId());
        }
    }

    public function test_json_format_writes_one_parsable_object_per_line(): void
    {
        Config::set('logging.format', 'json');

        $lines = $this->logged(function () use (&$response) {
            $response = $this->get('/np-trace');
        });
        $entry = json_decode((string) end($lines), true);

        $this->assertIsArray($entry);
        $this->assertSame('info', $entry['level']);
        $this->assertSame('np-trace dans la requête', $entry['message']);
        $this->assertSame(['commande' => 42], $entry['context']);
        $this->assertSame($response->header('X-Request-Id'), $entry['request_id']);
        $this->assertNotFalse(\DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $entry['timestamp']));
    }

    public function test_json_logs_survive_exceptions_objects_and_invalid_utf8(): void
    {
        Config::set('logging.format', 'json');

        $lines = $this->logged(fn () => Log::error('np-json {nom}', [
            'nom' => "Awa \xB1",
            'exception' => new \RuntimeException('boum'),
            'objet' => new \stdClass(),
            'password' => 'secret',
        ]));
        $entry = json_decode((string) end($lines), true);

        $this->assertIsArray($entry);
        $this->assertSame(\RuntimeException::class, $entry['context']['exception']['class']);
        $this->assertSame('boum', $entry['context']['exception']['message']);
        $this->assertSame('[stdClass]', $entry['context']['objet']);
        $this->assertSame('[masqué]', $entry['context']['password']);
        $this->assertStringStartsWith('np-json Awa ', $entry['message']);
        $this->assertArrayNotHasKey('request_id', $entry, 'hors requête HTTP');
    }

    public function test_shared_context_is_added_to_every_following_message(): void
    {
        Log::withContext(['tenant' => 'dakar', 'api_token' => 'x']);

        $lines = $this->logged(function () {
            Log::info('np-partage-1');
            Log::info('np-partage-2 {tenant}');
        });

        $this->assertCount(2, $lines);
        $this->assertStringContainsString('"tenant":"dakar"', $lines[0]);
        $this->assertStringContainsString('"api_token":"[masqué]"', $lines[0]);
        $this->assertStringContainsString('np-partage-2 dakar', $lines[1]);
    }

    public function test_outgoing_http_calls_join_the_trace_of_the_request(): void
    {
        // Un vrai serveur qui renvoie les en-têtes reçus : ce qui part réellement sur le réseau.
        $dir = sys_get_temp_dir() . '/np-trace-' . bin2hex(random_bytes(4));
        mkdir($dir);
        file_put_contents("$dir/index.php", '<?php echo json_encode(getallheaders());');
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertIsResource($socket);
        $address = (string) stream_socket_get_name($socket, false);
        $port = (int) substr($address, strrpos($address, ':') + 1);
        fclose($socket);
        $server = proc_open([PHP_BINARY, '-S', "127.0.0.1:$port", '-t', $dir], [1 => ['file', $dir . '/out', 'w'], 2 => ['file', $dir . '/out', 'w']], $pipes);
        $this->assertIsResource($server);

        try {
            for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) {
                usleep(100_000);
            }

            $this->get('/np-trace', ['traceparent' => '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01']);
            $received = array_change_key_case((array) json_decode(Client::request('GET', "http://127.0.0.1:$port/")['body'], true));

            $this->assertSame(Trace::traceparent(), $received['traceparent'] ?? null);
            $this->assertStringStartsWith('00-4bf92f3577b34da6a3ce929d0e0e4736-', $received['traceparent']);

            // Un traceparent choisi par l'appelant n'est pas remplacé ; hors requête, rien n'est ajouté.
            $explicit = '00-11111111111111111111111111111111-2222222222222222-00';
            $received = array_change_key_case((array) json_decode(Client::request('GET', "http://127.0.0.1:$port/", ['Traceparent' => $explicit])['body'], true));
            $this->assertSame($explicit, $received['traceparent']);

            Trace::reset();
            $received = array_change_key_case((array) json_decode(Client::request('GET', "http://127.0.0.1:$port/")['body'], true));
            $this->assertArrayNotHasKey('traceparent', $received);
        } finally {
            proc_terminate($server);
            proc_close($server);
            array_map('unlink', glob("$dir/*") ?: []);
            rmdir($dir);
        }
    }

    public function test_a_queued_job_logs_with_the_request_id_of_the_request_that_queued_it(): void
    {
        Config::set('queue.driver', 'file');
        $this->app->router->get('/np-trace-job', function (): Response {
            Queue::push(new ObservabilityLoggingJob());

            return Response::html('ok');
        });

        $id = $this->get('/np-trace-job', ['X-Request-Id' => 'commande-1234'])->header('X-Request-Id');
        Trace::reset();   // le worker est un autre process

        $lines = $this->logged(fn () => Queue::work());

        $this->assertSame('commande-1234', $id);
        $this->assertStringContainsString('"request_id":"commande-1234"', implode("\n", $lines));
        $this->assertSame([], Log::sharedContext(), 'le contexte ne déborde pas sur le job suivant');
    }
}

class ObservabilityLoggingJob extends Job
{
    public function handle(): void
    {
        Log::info('np-trace depuis le worker');
    }
}
