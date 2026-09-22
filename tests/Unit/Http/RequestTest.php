<?php

namespace Tests\Unit\Http;

use Niang\Core\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function test_create_splits_a_query_string_embedded_in_the_uri(): void
    {
        $request = Request::create('GET', '/posts?page=2');

        $this->assertSame('/posts', $request->uri);
        $this->assertSame(['page' => '2'], $request->query);
    }

    public function test_create_keeps_an_embedded_query_string_separate_from_the_body_on_post(): void
    {
        // Nécessaire pour tester une route signée protégée par ValidateSignature en POST
        // (ex: AuthController::resetPassword) : la signature vit dans la query string de l'URL
        // d'action, pas dans le corps du formulaire.
        $request = Request::create('POST', '/reset-password?expires=123&signature=abc', ['password' => 'secret']);

        $this->assertSame('/reset-password', $request->uri);
        $this->assertSame(['expires' => '123', 'signature' => 'abc'], $request->query);
        $this->assertSame(['password' => 'secret'], $request->body);
    }

    public function test_create_without_a_query_string_behaves_as_before(): void
    {
        $get = Request::create('GET', '/posts', ['page' => '3']);
        $this->assertSame(['page' => '3'], $get->query);
        $this->assertSame([], $get->body);

        $post = Request::create('POST', '/posts', ['title' => 'Bonjour']);
        $this->assertSame([], $post->query);
        $this->assertSame(['title' => 'Bonjour'], $post->body);
    }
}
