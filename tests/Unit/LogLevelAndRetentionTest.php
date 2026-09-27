<?php

namespace Tests\Unit;

use Niang\Core\Config;
use Niang\Core\Log;
use PHPUnit\Framework\TestCase;

class LogLevelAndRetentionTest extends TestCase
{
    private string $logFile;
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Config::load(base_path());
        $this->dir = base_path('storage/logs');
        $this->logFile = $this->dir . '/' . date('Y-m-d') . '.log';
    }

    protected function tearDown(): void
    {
        Config::load(base_path());
        parent::tearDown();
    }

    private function logged(\Closure $write): string
    {
        $offset = is_file($this->logFile) ? (int) filesize($this->logFile) : 0;
        $write();
        clearstatcache();

        return is_file($this->logFile) ? substr((string) file_get_contents($this->logFile), $offset) : '';
    }

    public function test_messages_below_the_minimum_level_are_skipped(): void
    {
        Config::set('logging.level', 'warning');

        $output = $this->logged(function () {
            Log::debug('np-debug-ignoré');
            Log::info('np-info-ignoré');
            Log::warning('np-warning-écrit');
            Log::critical('np-critical-écrit');
        });

        $this->assertStringNotContainsString('np-debug-ignoré', $output);
        $this->assertStringNotContainsString('np-info-ignoré', $output);
        $this->assertStringContainsString('WARNING: np-warning-écrit', $output);
        $this->assertStringContainsString('CRITICAL: np-critical-écrit', $output);
    }

    public function test_an_unknown_configured_level_logs_everything_rather_than_nothing(): void
    {
        Config::set('logging.level', 'verbeux');

        $this->assertTrue(Log::shouldLog('debug'));
        $this->assertTrue(Log::shouldLog('emergency'));
    }

    public function test_sensitive_context_keys_are_masked_at_any_depth(): void
    {
        $output = $this->logged(fn () => Log::info('np-connexion {password}', [
            'email' => 'awa@example.test',
            'password' => 'motdepasse123',
            'request' => ['api_key' => 'sk_live_123', 'Authorization' => 'Bearer abc', 'page' => 2],
            'remember_token' => 'jeton',
            'card_number' => '4242424242424242',
        ]));

        $this->assertStringContainsString('awa@example.test', $output);
        $this->assertStringContainsString('"page":2', $output);
        foreach (['motdepasse123', 'sk_live_123', 'Bearer abc', 'jeton"', '4242424242424242'] as $secret) {
            $this->assertStringNotContainsString($secret, $output);
        }
        $this->assertStringContainsString('np-connexion [masqué]', $output, 'le message interpolé est masqué aussi');
    }

    public function test_prune_removes_only_log_files_older_than_the_retention(): void
    {
        Config::set('logging.days', 3);
        $dir = sys_get_temp_dir() . '/np-logs-' . uniqid();
        mkdir($dir);

        $files = [];
        foreach ([0, 2, 3, 30] as $daysAgo) {
            $files[$daysAgo] = "$dir/" . date('Y-m-d', strtotime("-$daysAgo days")) . '.log';
            touch($files[$daysAgo]);
        }
        touch("$dir/notes.log");

        $this->assertSame(2, Log::prune($dir));

        $this->assertFileExists($files[0]);
        $this->assertFileExists($files[2]);
        $this->assertFileDoesNotExist($files[3]);
        $this->assertFileDoesNotExist($files[30]);
        $this->assertFileExists("$dir/notes.log", 'un fichier qui ne suit pas le nommage par date est ignoré');

        array_map('unlink', glob("$dir/*") ?: []);
        rmdir($dir);
    }

    public function test_zero_days_keeps_everything(): void
    {
        Config::set('logging.days', 0);
        $dir = sys_get_temp_dir() . '/np-logs-' . uniqid();
        mkdir($dir);
        touch("$dir/2000-01-01.log");

        $this->assertSame(0, Log::prune($dir));
        $this->assertFileExists("$dir/2000-01-01.log");

        unlink("$dir/2000-01-01.log");
        rmdir($dir);
    }

    public function test_config_set_supports_dot_notation(): void
    {
        Config::set('logging.level', 'error');
        Config::set('nouveau.section.cle', 'valeur');

        $this->assertSame('error', Config::get('logging.level'));
        $this->assertSame('valeur', Config::get('nouveau.section.cle'));
        $this->assertSame(14, Config::get('logging.days'), 'les autres clés de la section sont conservées');
    }

    public function test_single_channel_writes_one_file(): void
    {
        Config::set('logging.channel', 'single');
        $file = base_path('storage/logs/niangpro.log');
        $offset = is_file($file) ? (int) filesize($file) : 0;

        Log::warning('np-canal-single');
        clearstatcache();

        $this->assertStringContainsString('WARNING: np-canal-single', substr((string) file_get_contents($file), $offset));
    }

    public function test_errorlog_channel_uses_php_error_log(): void
    {
        Config::set('logging.channel', 'errorlog');
        $target = tempnam(sys_get_temp_dir(), 'np-errorlog');
        $previous = ini_set('error_log', $target);

        try {
            Log::error('np-canal-errorlog {id}', ['id' => 7, 'password' => 'secret']);
        } finally {
            ini_set('error_log', (string) $previous);
        }

        $written = (string) file_get_contents($target);
        unlink($target);
        $this->assertStringContainsString('ERROR: np-canal-errorlog 7', $written);
        $this->assertStringNotContainsString('"secret"', $written, 'masquage appliqué sur tous les canaux');
    }

    public function test_stderr_channel_writes_to_standard_error(): void
    {
        $script = sprintf(
            'require %s; Niang\\Core\\Config::load(%s); Niang\\Core\\Config::set("logging.channel", "stderr"); Niang\\Core\\Log::info("np-canal-stderr");',
            var_export(base_path('vendor/autoload.php'), true),
            var_export(base_path(), true)
        );
        $process = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if ($process === false) {
            $this->fail('Impossible de lancer PHP.');
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        proc_close($process);

        $this->assertSame('', $stdout);
        $this->assertStringContainsString('INFO: np-canal-stderr', $stderr);
    }

    public function test_an_unknown_channel_falls_back_to_daily(): void
    {
        Config::set('logging.channel', 'papier');

        $this->assertSame('daily', Log::channel());
    }
}
