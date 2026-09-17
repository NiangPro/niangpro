<?php

namespace Tests\Feature;

use App\Models\Post;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

class JsonResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_wraps_records_in_data_with_meta_and_links(): void
    {
        for ($i = 0; $i < 3; $i++) {
            Post::create(['title' => "Post $i", 'body' => 'Contenu']);
        }

        $response = $this->get('/api/posts');

        $response->assertOk();
        $payload = $response->json();

        $this->assertCount(3, $payload['data']);
        $this->assertArrayHasKey('id', $payload['data'][0]);
        $this->assertArrayHasKey('comments_count', $payload['data'][0]);
        $this->assertArrayNotHasKey('created_at', $payload['data'][0]); // toArray() filtre les champs

        $this->assertSame([
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 10,
            'total' => 3,
        ], $payload['meta']);

        $this->assertNull($payload['links']['prev']);
        $this->assertNull($payload['links']['next']);
    }

    public function test_links_reflect_the_current_page(): void
    {
        for ($i = 0; $i < 15; $i++) {
            Post::create(['title' => "Post $i", 'body' => 'Contenu']);
        }

        $response = $this->call('GET', '/api/posts', ['page' => 2]);
        $payload = $response->json();

        $this->assertCount(5, $payload['data']);
        $this->assertSame('?page=1', $payload['links']['prev']);
        $this->assertNull($payload['links']['next']);
    }
}
