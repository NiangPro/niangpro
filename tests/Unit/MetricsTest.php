<?php

namespace Tests\Unit;

use Niang\Core\Cache;
use Niang\Core\Config;
use Niang\Core\Http\Response;
use Niang\Core\Job;
use Niang\Core\Metrics;
use Niang\Core\Queue;
use Niang\Core\Testing\TestCase;

class MetricsTest extends TestCase
{
    private const TOKEN = 'jeton-de-test-prometheus';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('cache.driver', 'array');
        Cache::flush();
        Config::set('metrics.enabled', true);
        Config::set('metrics.token', self::TOKEN);
        Config::set('metrics.counters', ['orders_created_total' => 'Commandes créées']);

        $this->app->router->get('/np-metrics/ok', fn () => Response::html('ok'));
        $this->app->router->post('/np-metrics/ok', fn () => Response::html('ok', 201));
        $this->app->router->get('/np-metrics/boum', fn () => throw new \RuntimeException('boum'));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Config::load(base_path());
        parent::tearDown();
    }

    private function scrape(): string
    {
        $response = $this->get('/metrics', ['Authorization' => 'Bearer ' . self::TOKEN]);
        $this->assertSame(200, $response->status());
        $this->assertStringStartsWith('text/plain; version=0.0.4', (string) $response->header('Content-Type'));

        return $response->content();
    }

    public function test_requests_are_counted_by_method_and_status_class(): void
    {
        $this->get('/np-metrics/ok');
        $this->get('/np-metrics/ok');
        $this->post('/np-metrics/ok');
        $this->get('/np-metrics/introuvable');
        $this->get('/np-metrics/boum');

        $text = $this->scrape();

        $this->assertStringContainsString('niangpro_http_requests_total{method="GET",status="2xx"} 2', $text);
        $this->assertStringContainsString('niangpro_http_requests_total{method="POST",status="2xx"} 1', $text);
        $this->assertStringContainsString('niangpro_http_requests_total{method="GET",status="4xx"} 1', $text);
        $this->assertStringContainsString('niangpro_http_requests_total{method="GET",status="5xx"} 1', $text);
        $this->assertStringContainsString('niangpro_http_request_duration_seconds_bucket{le="+Inf"} 5', $text);
        $this->assertStringContainsString('niangpro_http_request_duration_seconds_count 5', $text);
        $this->assertStringNotContainsString('introuvable', $text, 'jamais l\'URL : le nombre de séries reste borné');
    }

    public function test_the_histogram_is_cumulative_and_the_scrape_itself_is_not_counted(): void
    {
        Metrics::recordRequest('GET', 200, 0.003);
        Metrics::recordRequest('GET', 200, 0.2);
        Metrics::recordRequest('GET', 200, 30);
        $this->scrape();

        $text = $this->scrape();

        $this->assertStringContainsString('niangpro_http_request_duration_seconds_bucket{le="0.005"} 1', $text);
        $this->assertStringContainsString('niangpro_http_request_duration_seconds_bucket{le="0.1"} 1', $text);
        $this->assertStringContainsString('niangpro_http_request_duration_seconds_bucket{le="0.25"} 2', $text);
        $this->assertStringContainsString('niangpro_http_request_duration_seconds_bucket{le="10"} 2', $text);
        $this->assertStringContainsString('niangpro_http_request_duration_seconds_bucket{le="+Inf"} 3', $text);
        $this->assertStringContainsString('niangpro_http_request_duration_seconds_sum 30.203', $text);
    }

    public function test_the_endpoint_needs_the_token_and_is_hidden_without_one(): void
    {
        $this->assertSame(401, $this->get('/metrics')->status());
        $this->assertSame(401, $this->get('/metrics', ['Authorization' => 'Bearer mauvais'])->status());
        $this->assertSame('Bearer', $this->get('/metrics')->header('WWW-Authenticate'));

        Config::set('metrics.token', '');
        $this->assertSame(404, $this->get('/metrics', ['Authorization' => 'Bearer '])->status());

        Config::set('metrics.enabled', false);
        Config::set('metrics.token', self::TOKEN);
        $this->assertSame(404, $this->get('/metrics', ['Authorization' => 'Bearer ' . self::TOKEN])->status());
    }

    public function test_nothing_is_recorded_when_disabled(): void
    {
        Config::set('metrics.enabled', false);
        $this->get('/np-metrics/ok');
        Metrics::increment('orders_created_total');

        Config::set('metrics.enabled', true);
        $text = $this->scrape();

        $this->assertStringNotContainsString('niangpro_http_requests_total{', $text);
        $this->assertStringContainsString("app_orders_created_total 0\n", $text);
    }

    public function test_application_counters_and_gauges(): void
    {
        Metrics::increment('orders_created_total');
        Metrics::increment('orders_created_total', 2);
        Metrics::gauge('queue_size', 'Jobs en attente', fn () => 12);
        Metrics::gauge('broken', 'Jauge en échec', fn () => throw new \RuntimeException('base indisponible'));

        $text = $this->scrape();

        $this->assertStringContainsString("# HELP app_orders_created_total Commandes créées\n# TYPE app_orders_created_total counter\napp_orders_created_total 3\n", $text);
        $this->assertStringContainsString("# TYPE app_queue_size gauge\napp_queue_size 12\n", $text);
        $this->assertStringNotContainsString('app_broken', $text, 'une jauge en échec est omise, les autres restent');
    }

    public function test_an_undeclared_counter_is_a_programming_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Metrics::increment('non_declare_total');
    }

    public function test_worker_jobs_are_counted(): void
    {
        Config::set('queue.driver', 'file');
        Queue::push(new MetricsOkJob());
        Queue::push(new MetricsFailingJob());
        Queue::work();

        $text = $this->scrape();

        $this->assertStringContainsString("niangpro_jobs_processed_total 1\n", $text);
        $this->assertStringContainsString("niangpro_jobs_failed_total 1\n", $text);
    }

    public function test_the_output_is_valid_prometheus_text(): void
    {
        $this->get('/np-metrics/ok');
        Metrics::gauge('ratio', "Avec \\ et\nretour", fn () => 0.5);

        foreach (array_filter(explode("\n", $this->scrape())) as $line) {
            $this->assertMatchesRegularExpression(
                '/^(# (HELP|TYPE) [a-zA-Z_:][a-zA-Z0-9_:]* .+|[a-zA-Z_:][a-zA-Z0-9_:]*(\{[a-z_]+="[^"]*"(,[a-z_]+="[^"]*")*\})? -?[0-9.e+]+|[a-zA-Z_:][a-zA-Z0-9_:]*\{le="\+Inf"\} [0-9]+)$/',
                $line
            );
        }
    }

    public function test_flush_resets_every_counter(): void
    {
        $this->get('/np-metrics/ok');
        Metrics::increment('orders_created_total');

        Metrics::flush();
        $text = $this->scrape();

        $this->assertStringNotContainsString('niangpro_http_requests_total{', $text);
        $this->assertStringContainsString("app_orders_created_total 0\n", $text);
    }
}

class MetricsOkJob extends Job
{
    public function handle(): void
    {
    }
}

class MetricsFailingJob extends Job
{
    public function handle(): void
    {
        throw new \RuntimeException('échec voulu');
    }
}
