<?php

namespace Niang\Core\Testing;

use Niang\Core\Http\Response;
use PHPUnit\Framework\Assert;

class TestResponse
{
    public function __construct(private Response $response)
    {
    }

    public function assertStatus(int $status): static
    {
        Assert::assertSame($status, $this->response->getStatus(), sprintf(
            "Statut attendu %d, obtenu %d.\n%s",
            $status,
            $this->response->getStatus(),
            $this->response->getContent()
        ));

        return $this;
    }

    public function assertOk(): static
    {
        return $this->assertStatus(200);
    }

    public function assertRedirect(?string $to = null): static
    {
        Assert::assertContains($this->response->getStatus(), [301, 302], 'La réponse n\'est pas une redirection.');

        if ($to !== null) {
            Assert::assertSame($to, $this->response->getHeader('Location'));
        }

        return $this;
    }

    public function assertSee(string $text): static
    {
        Assert::assertStringContainsString($text, $this->response->getContent());
        return $this;
    }

    public function assertDontSee(string $text): static
    {
        Assert::assertStringNotContainsString($text, $this->response->getContent());
        return $this;
    }

    public function assertJson(array $subset): static
    {
        $data = $this->json();
        Assert::assertIsArray($data);

        foreach ($subset as $key => $value) {
            Assert::assertArrayHasKey($key, $data);
            Assert::assertSame($value, $data[$key]);
        }

        return $this;
    }

    public function json(): mixed
    {
        return json_decode($this->response->getContent(), true);
    }

    public function content(): string
    {
        return $this->response->getContent();
    }

    public function status(): int
    {
        return $this->response->getStatus();
    }
}
