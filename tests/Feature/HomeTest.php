<?php

namespace Tests\Feature;

use Niang\Core\Testing\TestCase;

class HomeTest extends TestCase
{
    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('NiangPro');
    }

    public function test_unknown_route_returns_404(): void
    {
        $this->get('/route-inexistante')->assertStatus(404);
    }

    public function test_hello_route_returns_json_greeting(): void
    {
        $this->get('/hello/Test')->assertOk()->assertJson(['message' => 'Bonjour, Test !']);
    }
}
