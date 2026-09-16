<?php

namespace Tests\Unit;

use Niang\Core\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Logger (PSR-3) délègue à Niang\Core\Log — même fichier de sortie (storage/logs/AAAA-MM-JJ.log).
 * On ne compare que le contenu ajouté par le test (offset avant/après), le fichier étant partagé
 * avec d'autres tests qui logguent dans la même suite.
 */
class LoggerTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = dirname(__DIR__, 2) . '/storage/logs/' . date('Y-m-d') . '.log';
    }

    private function appendedSince(int $offset): string
    {
        $contents = is_file($this->logFile) ? file_get_contents($this->logFile) : '';
        return substr($contents, $offset);
    }

    private function currentOffset(): int
    {
        return is_file($this->logFile) ? filesize($this->logFile) : 0;
    }

    public function test_implements_psr3_logger_interface(): void
    {
        $this->assertInstanceOf(LoggerInterface::class, new Logger());
    }

    public function test_level_methods_delegate_to_log(): void
    {
        $offset = $this->currentOffset();

        (new Logger())->warning('Espace disque bas');

        $this->assertStringContainsString('WARNING: Espace disque bas', $this->appendedSince($offset));
    }

    public function test_context_interpolation(): void
    {
        $offset = $this->currentOffset();

        (new Logger())->error('Échec pour {user}', ['user' => 'awa']);

        $this->assertStringContainsString('ERROR: Échec pour awa', $this->appendedSince($offset));
    }
}
