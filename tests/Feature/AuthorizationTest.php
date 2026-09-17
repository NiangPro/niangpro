<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Niang\Core\Auth;
use Niang\Core\Hash;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/**
 * Exerce DELETE /posts/{id} (Authenticate + $this->authorize('post.delete', ...), résolu par
 * Gate::policy('post', PostPolicy::class) déclaré dans routes/web.php) — un chemin jusqu'ici non
 * couvert par les tests.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete_a_post(): void
    {
        $postId = Post::create(['title' => 'Article', 'body' => 'Contenu']);

        $this->delete("/posts/$postId")->assertRedirect();

        $this->assertNotNull(Post::find($postId));
    }

    public function test_authenticated_user_can_delete_a_post_via_the_policy(): void
    {
        $userId = User::create([
            'name' => 'Awa',
            'email' => 'awa@example.test',
            'password' => Hash::make('motdepasse123'),
        ]);
        Auth::login(['id' => $userId]);

        $postId = Post::create(['title' => 'Article', 'body' => 'Contenu']);

        $this->delete("/posts/$postId")->assertJson(['deleted' => true]);

        $this->assertNull(Post::find($postId));
    }
}
