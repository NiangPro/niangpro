<?php

declare(strict_types=1);

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

    /**
     * Le JSON contient $subset : les objets imbriqués (tableaux à clés) sont comparés en sous-ensemble,
     * à toute profondeur — assertJson(['data' => ['email' => ...]]) ignore les autres champs de data.
     * Une liste est comparée exactement.
     */
    public function assertJson(array $subset): static
    {
        $data = $this->json();
        Assert::assertIsArray($data);
        self::assertSubset($subset, $data, '');

        return $this;
    }

    private static function assertSubset(array $subset, array $data, string $path): void
    {
        foreach ($subset as $key => $value) {
            Assert::assertArrayHasKey($key, $data, "Clé absente du JSON : $path$key");

            if (is_array($value) && $value !== [] && !array_is_list($value) && is_array($data[$key])) {
                self::assertSubset($value, $data[$key], "$path$key.");
            } else {
                Assert::assertSame($value, $data[$key], "Valeur différente pour $path$key");
            }
        }
    }

    public function json(): mixed
    {
        return json_decode($this->response->getContent(), true);
    }

    public function content(): string
    {
        return $this->response->getContent();
    }

    public function header(string $key): ?string
    {
        return $this->response->getHeader($key);
    }

    public function status(): int
    {
        return $this->response->getStatus();
    }

    /** Valeur en clair d'un cookie posé par Response::cookie() ; null s'il n'est pas posé ou est supprimé. */
    public function cookie(string $name): ?string
    {
        return $this->response->getCookies()[$name]['value'] ?? null;
    }

    public function assertCookieForgotten(string $name): static
    {
        $cookies = $this->response->getCookies();

        Assert::assertTrue(isset($cookies[$name]) && $cookies[$name]['value'] === null, "Le cookie « $name » n'est pas supprimé par la réponse.");

        return $this;
    }

    public function assertCookie(string $name, ?string $value = null): static
    {
        $cookies = $this->response->getCookies();

        Assert::assertTrue(isset($cookies[$name]) && $cookies[$name]['value'] !== null, "Cookie « $name » absent de la réponse.");

        if ($value !== null) {
            Assert::assertSame($value, $cookies[$name]['value']);
        }

        return $this;
    }
}
