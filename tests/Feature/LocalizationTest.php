<?php

namespace Tests\Feature;

use Niang\Core\Lang;
use Niang\Core\Testing\TestCase;

class LocalizationTest extends TestCase
{
    protected function tearDown(): void
    {
        Lang::reset();
        parent::tearDown();
    }

    public function test_error_pages_are_in_french_by_default(): void
    {
        $this->get('/page-inexistante')->assertStatus(404)
            ->assertSee('<html lang="fr">')
            ->assertSee('Cette page n&#039;existe pas.');
    }

    public function test_error_pages_follow_the_locale(): void
    {
        Lang::setLocale('en');

        $this->get('/page-inexistante')->assertStatus(404)
            ->assertSee('<html lang="en">')
            ->assertSee('This page does not exist.')
            ->assertSee('Back to home');
    }

    public function test_json_validation_errors_follow_the_locale(): void
    {
        Lang::setLocale('en');

        $response = $this->post('/contact', ['_token' => \Niang\Core\Csrf::token()], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $this->assertSame('The given data was invalid.', $response->json()['message']);
        $this->assertSame(['The name field is required.'], $response->json()['errors']['name']);
    }

    public function test_the_csrf_error_follows_the_locale(): void
    {
        Lang::setLocale('en');

        $this->post('/contact', [], ['Accept' => 'application/json'])
            ->assertStatus(419)
            ->assertSee('Invalid or expired CSRF token');
    }
}
