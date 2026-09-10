<?php

namespace Tests\Feature;

use Niang\Core\Testing\TestCase;

class PostsTest extends TestCase
{
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

    public function test_blog_page_is_paginated(): void
    {
        $this->get('/blog')->assertOk()->assertSee('Articles');
    }
}
