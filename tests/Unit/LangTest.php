<?php

namespace Tests\Unit;

use Niang\Core\Database\Paginator;
use Niang\Core\Exceptions\HttpException;
use Niang\Core\Http\UploadedFile;
use Niang\Core\Lang;
use Niang\Core\Validation\ValidationException;
use Niang\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

class LangTest extends TestCase
{
    protected function tearDown(): void
    {
        Lang::reset();
        parent::tearDown();
    }

    /** @return array<string, list<string>> */
    private function errors(array $data, array $rules, array $attributes = []): array
    {
        try {
            Validator::make($data, $rules, [], $attributes)->validate();
            return [];
        } catch (ValidationException $e) {
            return $e->errors;
        }
    }

    public function test_french_is_the_default_locale(): void
    {
        $this->assertSame('fr', Lang::locale());
        $this->assertSame('Le champ email est requis.', __('validation.required', ['attribute' => 'email']));
    }

    public function test_validation_messages_follow_the_locale(): void
    {
        Lang::setLocale('en');

        $this->assertSame(['email' => ['The email field is required.']], $this->errors([], ['email' => 'required']));
        $this->assertSame(
            ['age' => ['The age field must be between 18 and 99.']],
            $this->errors(['age' => 5], ['age' => 'between:18,99'])
        );
        $this->assertSame(
            ['password_confirmation' => ['The password_confirmation field must match password.']],
            $this->errors(['password' => 'a', 'password_confirmation' => 'b'], ['password_confirmation' => 'same:password'])
        );
    }

    public function test_file_size_messages_are_translated_too(): void
    {
        Lang::setLocale('en');

        $errors = $this->errors(['f' => UploadedFile::fake('a.txt', str_repeat('a', 3 * 1024))], ['f' => 'file|max:2']);

        $this->assertSame(['The f file must not exceed 2 KB.'], $errors['f']);
    }

    public function test_attribute_labels_passed_to_the_validator_win(): void
    {
        Lang::setLocale('en');

        $this->assertSame(['email' => ['The email address field is required.']], $this->errors([], ['email' => 'required'], ['email' => 'email address']));
    }

    public function test_attribute_labels_can_come_from_the_translation_file(): void
    {
        $dir = base_path('lang/xx');
        mkdir($dir);
        file_put_contents("$dir/validation.php", "<?php return ['required' => ':Attribute obligatoire.', 'attributes' => ['items.*.name' => 'nom de l\\'article']];");

        try {
            Lang::setLocale('xx');
            $errors = $this->errors(['items' => [['name' => '']]], ['items.*.name' => 'required']);
            $this->assertSame(["Nom de l'article obligatoire."], $errors['items.0.name']);
        } finally {
            unlink("$dir/validation.php");
            rmdir($dir);
        }
    }

    public function test_a_missing_key_falls_back_to_french_then_to_the_key_itself(): void
    {
        Lang::setLocale('de'); // aucune traduction allemande livrée

        $this->assertSame('Le champ email est requis.', __('validation.required', ['attribute' => 'email']));
        $this->assertSame('validation.inexistante', __('validation.inexistante'));
        $this->assertSame('pas-une-cle', __('pas-une-cle'));
    }

    public function test_longer_placeholders_are_replaced_first(): void
    {
        $dir = base_path('lang/xx');
        mkdir($dir);
        file_put_contents("$dir/test.php", "<?php return ['ligne' => ':min minimum, :minutes minutes, :Min'];");

        try {
            $this->assertSame('trois minimum, 10 minutes, Trois', Lang::get('test.ligne', ['min' => 'trois', 'minutes' => '10'], 'xx'));
        } finally {
            unlink("$dir/test.php");
            rmdir($dir);
        }
    }

    public function test_http_errors_upload_errors_and_pagination_are_translated(): void
    {
        Lang::setLocale('en');

        $this->assertSame('Page not found.', (new HttpException(404))->getMessage());
        $this->assertSame('HTTP error 418.', (new HttpException(418))->getMessage());
        $this->assertSame('The given data was invalid.', (new ValidationException([]))->getMessage());
        $this->assertStringContainsString('maximum size allowed by the server', (new UploadedFile('', 'x.mp4', null, UPLOAD_ERR_INI_SIZE))->errorMessage());

        $links = (new Paginator([], 30, 10, 2))->links('/blog');
        $this->assertStringContainsString('&laquo; Previous', $links);
        $this->assertStringContainsString('Next &raquo;', $links);
    }

    public function test_invalid_locale_codes_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Lang::setLocale('../../etc');
    }
}
