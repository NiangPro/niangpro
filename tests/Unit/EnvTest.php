<?php

namespace Tests\Unit;

use Niang\Core\Env;
use PHPUnit\Framework\TestCase;

/**
 * Env::load() ne lit le fichier qu'une fois par process (déjà fait au démarrage des tests) : chaque
 * test remet le drapeau à false, charge un fichier temporaire avec des clés propres au test, puis le
 * remet à true.
 */
class EnvTest extends TestCase
{
    private string $file;

    /** @var list<string> */
    private array $keys = ['NP_ENV_SIMPLE', 'NP_ENV_QUOTED', 'NP_ENV_SINGLE', 'NP_ENV_EQUALS', 'NP_ENV_EMPTY', 'NP_ENV_SPACES', 'NP_ENV_REAL', 'NP_ENV_COMMENTED'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = tempnam(sys_get_temp_dir(), 'np-env');
    }

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            putenv($key);
        }

        @unlink($this->file);
        $this->setLoaded(true);
        parent::tearDown();
    }

    private function setLoaded(bool $loaded): void
    {
        (new \ReflectionProperty(Env::class, 'loaded'))->setValue(null, $loaded);
    }

    private function load(string $content): void
    {
        file_put_contents($this->file, $content);
        $this->setLoaded(false);
        Env::load($this->file);
    }

    public function test_it_parses_values_comments_quotes_and_equals_signs(): void
    {
        $this->load(<<<'ENV'
        # commentaire
        NP_ENV_SIMPLE=valeur
        NP_ENV_QUOTED="Mon site"
        NP_ENV_SINGLE='simple'
        NP_ENV_EQUALS=base64:abc==
        NP_ENV_EMPTY=
          NP_ENV_SPACES = avec espaces  
        # NP_ENV_COMMENTED=non

        ENV);

        $this->assertSame('valeur', Env::get('NP_ENV_SIMPLE'));
        $this->assertSame('Mon site', Env::get('NP_ENV_QUOTED'));
        $this->assertSame('simple', Env::get('NP_ENV_SINGLE'));
        $this->assertSame('base64:abc==', Env::get('NP_ENV_EQUALS'), 'seul le premier = sépare la clé de la valeur');
        $this->assertSame('', Env::get('NP_ENV_EMPTY'));
        $this->assertSame('avec espaces', Env::get('NP_ENV_SPACES'));
        $this->assertNull(Env::get('NP_ENV_COMMENTED'));
    }

    public function test_the_real_environment_wins_over_the_file(): void
    {
        putenv('NP_ENV_REAL=depuis-le-serveur');

        $this->load("NP_ENV_REAL=depuis-le-fichier\n");

        $this->assertSame('depuis-le-serveur', Env::get('NP_ENV_REAL'));
    }

    public function test_the_file_is_read_only_once_per_process(): void
    {
        $this->load("NP_ENV_SIMPLE=premier\n");
        file_put_contents($this->file, "NP_ENV_SIMPLE=second\n");
        putenv('NP_ENV_SIMPLE');

        Env::load($this->file);

        $this->assertNull(Env::get('NP_ENV_SIMPLE'), 'un second load() ne relit pas le fichier');
    }

    public function test_a_missing_file_is_not_an_error_and_defaults_apply(): void
    {
        $this->setLoaded(false);
        Env::load('/nulle/part/.env');

        $this->assertSame('défaut', Env::get('NP_ENV_SIMPLE', 'défaut'));
    }
}
