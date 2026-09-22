<?php

namespace Tests\Unit;

use Niang\Core\HealthCheck;
use PHPUnit\Framework\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_reports_ok_for_every_service_in_a_healthy_environment(): void
    {
        $result = HealthCheck::run();

        $this->assertSame('ok', $result['status']);
        $this->assertSame(
            ['database' => 'ok', 'cache' => 'ok', 'storage' => 'ok', 'queue' => 'ok'],
            $result['services']
        );
    }
}
