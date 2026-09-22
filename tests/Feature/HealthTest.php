<?php

namespace Tests\Feature;

use Niang\Core\Testing\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_reports_ok_with_the_service_breakdown(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        $services = $response->json()['services'];
        foreach (['database', 'cache', 'storage', 'queue'] as $service) {
            $this->assertSame('ok', $services[$service]);
        }
    }

    public function test_up_endpoint_uses_the_same_logic_as_health(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }
}
