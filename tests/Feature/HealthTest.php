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

    public function test_ready_checks_dependencies_like_health(): void
    {
        $response = $this->get('/health/ready');

        $response->assertOk();
        $this->assertSame('ok', $response->json()['services']['database']);
    }

    public function test_live_only_says_the_process_answers(): void
    {
        $response = $this->get('/health/live');

        $response->assertOk();
        $this->assertSame(['status' => 'ok'], $response->json());
    }

    public function test_probes_stay_up_during_maintenance(): void
    {
        \Niang\Core\MaintenanceMode::activate();

        try {
            $this->get('/health/live')->assertOk();
            $this->get('/health/ready')->assertOk();
            $this->get('/')->assertStatus(503);
        } finally {
            \Niang\Core\MaintenanceMode::deactivate();
        }
    }
}
