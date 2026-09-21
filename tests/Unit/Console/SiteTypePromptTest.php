<?php

namespace Tests\Unit\Console;

use Niang\Core\Console\ProjectScaffolder;
use Niang\Core\Console\SiteTypePrompt;
use PHPUnit\Framework\TestCase;
use Tests\Support\UsesTempDirectory;

class SiteTypePromptTest extends TestCase
{
    use UsesTempDirectory;

    private SiteTypePrompt $prompt;

    /** @var list<string> */
    private array $written = [];

    /** @var list<string> */
    private array $questions = [];

    protected function setUp(): void
    {
        parent::setUp();
        putenv(SiteTypePrompt::ENV_VARIABLE);

        $scaffold = $this->makeTempDirectory();
        $this->writeFile($scaffold, 'themes/vitrine/theme.json', json_encode(['label' => 'Site vitrine', 'order' => 10]));
        $this->writeFile($scaffold, 'themes/blog/theme.json', json_encode(['label' => 'Blog', 'order' => 20]));

        $this->prompt = new SiteTypePrompt(new ProjectScaffolder($scaffold));
    }

    protected function tearDown(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE);
        $this->removeTempDirectories();
        parent::tearDown();
    }

    /** @param list<?string> $answers réponses successives ; un `ask` inattendu fait échouer le test */
    private function resolve(?string $flag, bool $interactive, array $answers = []): string
    {
        return $this->prompt->resolve(
            $flag,
            $interactive,
            function (string $question) use (&$answers): ?string {
                $this->questions[] = $question;
                $this->assertNotEmpty($answers, 'Question posée alors qu\'aucune réponse n\'était prévue.');

                return array_shift($answers);
            },
            function (string $text): void {
                $this->written[] = $text;
            }
        );
    }

    public function test_an_explicit_type_is_used_without_asking_anything(): void
    {
        $this->assertSame('blog', $this->resolve('blog', true));
        $this->assertSame([], $this->questions);
    }

    public function test_an_explicit_type_is_normalised(): void
    {
        $this->assertSame('blog', $this->resolve('  Blog ', false));
    }

    public function test_an_unknown_explicit_type_fails_and_lists_the_valid_ones(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('vitrine, blog, minimal');

        $this->resolve('boutique', false);
    }

    public function test_the_environment_variable_is_used_when_no_flag_is_given(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE . '=vitrine');

        $this->assertSame('vitrine', $this->resolve(null, true));
        $this->assertSame([], $this->questions);
    }

    public function test_the_flag_wins_over_the_environment_variable(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE . '=vitrine');

        $this->assertSame('blog', $this->resolve('blog', false));
    }

    public function test_an_empty_flag_falls_back_to_the_environment_variable(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE . '=vitrine');

        $this->assertSame('vitrine', $this->resolve('', false));
    }

    public function test_an_unknown_type_in_the_environment_variable_fails_too(): void
    {
        putenv(SiteTypePrompt::ENV_VARIABLE . '=nimporte-quoi');

        $this->expectException(\InvalidArgumentException::class);

        $this->resolve(null, false);
    }

    public function test_non_interactive_mode_never_asks_and_keeps_the_minimal_skeleton(): void
    {
        $this->assertSame('minimal', $this->resolve(null, false));
        $this->assertSame([], $this->questions);
        $this->assertSame([], $this->written);
    }

    public function test_interactive_mode_shows_the_numbered_catalog_with_the_default_marked(): void
    {
        $this->resolve(null, true, ['']);
        $output = implode('', $this->written);

        $this->assertStringContainsString('1) vitrine', $output);
        $this->assertStringContainsString('2) blog', $output);
        $this->assertStringContainsString('3) minimal', $output);
        $this->assertStringContainsString('(défaut)', $output);
        $this->assertSame(["\nVotre choix [3] : "], $this->questions);
    }

    public function test_an_empty_answer_selects_the_default(): void
    {
        $this->assertSame('minimal', $this->resolve(null, true, ['  ']));
    }

    public function test_end_of_input_selects_the_default_instead_of_looping(): void
    {
        $this->assertSame('minimal', $this->resolve(null, true, [null]));
        $this->assertCount(1, $this->questions);
    }

    public function test_a_number_selects_the_matching_entry(): void
    {
        $this->assertSame('blog', $this->resolve(null, true, ['2']));
    }

    public function test_a_slug_selects_the_matching_entry_case_insensitively(): void
    {
        $this->assertSame('vitrine', $this->resolve(null, true, [' Vitrine ']));
    }

    public function test_an_invalid_answer_asks_again(): void
    {
        $this->assertSame('blog', $this->resolve(null, true, ['9', 'oups', 'blog']));
        $this->assertCount(3, $this->questions);
        $this->assertStringContainsString('Choix invalide : « 9 »', implode('', $this->written));
    }

    public function test_three_invalid_answers_fall_back_to_the_default(): void
    {
        $this->assertSame('minimal', $this->resolve(null, true, ['0', 'x', 'y']));
        $this->assertCount(3, $this->questions);
        $this->assertStringContainsString('squelette minimal est conservé', implode('', $this->written));
    }
}
