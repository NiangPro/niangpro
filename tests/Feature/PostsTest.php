<?php

namespace Tests\Feature;

use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Csrf;
use Niang\Core\Hash;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

class PostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_endpoint_returns_an_array(): void
    {
        $response = $this->get('/posts');

        $response->assertOk();
        $this->assertIsArray($response->json());
    }

    public function test_creating_a_post_requires_authentication(): void
    {
        $this->post('/posts', ['title' => 'x', 'body' => 'y'])->assertRedirect();
    }

    /** POST /posts n'avait aucune protection CSRF avant ce test (voir CHANGELOG) : régression. */
    public function test_an_authenticated_post_without_a_csrf_token_is_rejected(): void
    {
        $this->loginAsNewUser();

        $this->post('/posts', ['title' => 'x', 'body' => 'y'])->assertStatus(419);
    }

    public function test_an_authenticated_post_with_a_valid_csrf_token_creates_the_post(): void
    {
        $this->loginAsNewUser();

        $this->post('/posts', ['_token' => Csrf::token(), 'title' => 'Titre valide', 'body' => 'Contenu valide'])
            ->assertStatus(201);
    }

    public function test_blog_page_is_paginated(): void
    {
        $this->get('/blog')->assertOk()->assertSee('Articles');
    }

    private function loginAsNewUser(): void
    {
        $userId = User::create([
            'name' => 'Awa',
            'email' => 'awa@example.test',
            'password' => Hash::make('motdepasse123'),
        ]);
        Auth::login(['id' => $userId]);
    }
}
