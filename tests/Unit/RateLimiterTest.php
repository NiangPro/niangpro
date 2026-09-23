<?php

namespace Tests\Unit;

use Niang\Core\RateLimiter;
use PHPUnit\Framework\TestCase;

/** Aucun test n'existait sur cette classe avant ce fichier, ni sur le comportement 429 qu'elle sert (ThrottleRequests). */
class RateLimiterTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (glob(base_path('storage/framework/ratelimits') . '/*.json') ?: [] as $file) {
            unlink($file);
        }

        parent::tearDown();
    }

    public function test_allows_up_to_max_attempts_then_blocks(): void
    {
        $key = 'test-' . uniqid();

        $this->assertTrue(RateLimiter::attempt($key, 3, 60));
        $this->assertTrue(RateLimiter::attempt($key, 3, 60));
        $this->assertTrue(RateLimiter::attempt($key, 3, 60));
        $this->assertFalse(RateLimiter::attempt($key, 3, 60));
        $this->assertFalse(RateLimiter::attempt($key, 3, 60));
    }

    public function test_different_keys_are_independent(): void
    {
        $a = 'test-a-' . uniqid();
        $b = 'test-b-' . uniqid();

        RateLimiter::attempt($a, 1, 60);

        $this->assertFalse(RateLimiter::attempt($a, 1, 60));
        $this->assertTrue(RateLimiter::attempt($b, 1, 60));
    }

    public function test_resets_after_the_decay_window_elapses(): void
    {
        $key = 'test-' . uniqid();

        RateLimiter::attempt($key, 1, 60);
        $this->assertFalse(RateLimiter::attempt($key, 1, 60));

        // Simule l'expiration : on avance resetAt dans le passé directement sur le fichier,
        // plutôt que de dépendre d'un vrai sleep() qui ralentirait la suite inutilement.
        $method = new \ReflectionMethod(RateLimiter::class, 'path');
        $method->setAccessible(true);
        $path = $method->invoke(null, $key);
        file_put_contents($path, json_encode(['count' => 1, 'resetAt' => time() - 1]));

        $this->assertTrue(RateLimiter::attempt($key, 1, 60));
    }

    public function test_available_in_reports_the_remaining_seconds(): void
    {
        $key = 'test-' . uniqid();

        RateLimiter::attempt($key, 5, 30);

        $availableIn = RateLimiter::availableIn($key);
        $this->assertGreaterThan(0, $availableIn);
        $this->assertLessThanOrEqual(30, $availableIn);
    }

    public function test_available_in_is_zero_for_an_unknown_key(): void
    {
        $this->assertSame(0, RateLimiter::availableIn('jamais-utilisee-' . uniqid()));
    }

    /**
     * La régression réelle : l'ancien code lisait le compteur, l'incrémentait en mémoire PHP,
     * puis l'écrivait — sans verrou couvrant tout le cycle. Deux requêtes concurrentes (le
     * scénario naturel d'une attaque par force brute par connexions parallèles plutôt que
     * séquentielles, contre /login notamment) pouvaient lire le même compteur avant qu'aucune
     * n'ait écrit sa mise à jour ; la seconde écriture écrasait la première, perdant un
     * incrément. pcntl_fork() simule cette concurrence avec de vrais process séparés (comme le
     * ferait PHP-FPM) — pas seulement des appels séquentiels dans le même process, qui ne
     * pourraient jamais reproduire la course.
     */
    public function test_concurrent_attempts_do_not_lose_increments(): void
    {
        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl indisponible sur cette plateforme (Windows notamment) — voir CHANGELOG.');
        }

        $key = 'concurrency-' . uniqid();
        $children = 10;
        $pids = [];

        for ($i = 0; $i < $children; $i++) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('pcntl_fork() a échoué.');
            }

            if ($pid === 0) {
                RateLimiter::attempt($key, 1000, 60);
                exit(0);
            }

            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        $method = new \ReflectionMethod(RateLimiter::class, 'read');
        $method->setAccessible(true);
        $data = $method->invoke(null, $key);

        $this->assertSame($children, $data['count']);
    }
}
