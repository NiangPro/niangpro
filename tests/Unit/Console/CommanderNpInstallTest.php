<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\Commander;
use PHPUnit\Framework\TestCase;

/**
 * np:install écrit dans un dossier du PATH : on isole HOME et PATH dans un dossier temporaire pour
 * ne jamais toucher la machine qui exécute les tests. La branche Windows ne peut pas s'exécuter ici
 * (PHP_OS_FAMILY est une constante) : on vérifie donc le script np.cmd généré et le choix des
 * dossiers, appelés directement avec $windows = true.
 */
class CommanderNpInstallTest extends TestCase
{
    private string $tmp;
    private string|false $home;
    private string|false $path;
    private string|false $localAppData;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/niang-np-' . bin2hex(random_bytes(4));
        mkdir($this->tmp . '/home', 0755, true);
        $this->home = getenv('HOME');
        $this->path = getenv('PATH');
        $this->localAppData = getenv('LOCALAPPDATA');
        putenv('HOME=' . $this->tmp . '/home');
    }

    protected function tearDown(): void
    {
        foreach (['HOME' => $this->home, 'PATH' => $this->path, 'LOCALAPPDATA' => $this->localAppData] as $name => $value) {
            putenv($value === false ? $name : "$name=$value");
        }

        exec('rm -rf ' . escapeshellarg($this->tmp));
    }

    private function call(string $method, mixed ...$args): mixed
    {
        $reflection = new \ReflectionMethod(Commander::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(new Commander(dirname(__DIR__, 3)), ...$args);
    }

    private function install(): string
    {
        ob_start();
        $this->call('npInstall');

        return (string) ob_get_clean();
    }

    public function test_installs_into_local_bin_and_explains_path_when_no_path_dir_is_writable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Branche macOS/Linux.');
        }

        putenv('PATH=/nonexistent-niang-dir');

        $output = $this->install();
        $script = $this->tmp . '/home/.local/bin/np';

        $this->assertFileExists($script);
        $this->assertTrue(is_executable($script));
        $this->assertStringContainsString('export PATH="$HOME/.local/bin:$PATH"', $output);
    }

    public function test_uses_a_writable_path_dir_without_path_instructions(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Branche macOS/Linux.');
        }

        mkdir($this->tmp . '/bin');
        putenv('PATH=' . $this->tmp . '/bin');

        $output = $this->install();

        $this->assertFileExists($this->tmp . '/bin/np');
        $this->assertStringNotContainsString('PATH', $output);
    }

    public function test_windows_script_walks_up_to_bin_niang_with_crlf_line_endings(): void
    {
        $script = $this->call('npWindowsScript');

        $this->assertStringStartsWith("@echo off\r\n", $script);
        $this->assertStringContainsString('php "%dir%\bin\niang" %*', $script);
        $this->assertStringNotContainsString("\n", str_replace("\r\n", '', $script));
    }

    public function test_windows_only_considers_personal_dirs_even_if_system_dirs_are_writable(): void
    {
        // Le dossier temporaire est accessible en écriture et dans le PATH, mais ce n'est pas un
        // dossier personnel connu : il ne doit pas être retenu.
        putenv('PATH=' . $this->tmp);
        putenv('LOCALAPPDATA=' . $this->tmp . '/home');

        $fallback = $this->call('npFallbackDir', true);

        $this->assertSame($this->tmp . '/home\NiangPro\bin', $fallback);
        $this->assertNull($this->call('findWritablePathDir', true, $fallback));
    }
}
