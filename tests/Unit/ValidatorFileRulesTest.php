<?php

namespace Tests\Unit;

use Niang\Core\Http\UploadedFile;
use Niang\Core\Validation\ValidationException;
use Niang\Core\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorFileRulesTest extends TestCase
{
    /** @return array<string, list<string>> les erreurs, ou [] si la validation passe */
    private function errors(array $data, array $rules): array
    {
        try {
            Validator::make($data, $rules)->validate();
            return [];
        } catch (ValidationException $e) {
            return $e->errors;
        }
    }

    public function test_file_rule_requires_an_uploaded_file(): void
    {
        $this->assertSame([], $this->errors(['doc' => UploadedFile::fake('cv.pdf', '%PDF-1.4')], ['doc' => 'required|file']));
        $this->assertArrayHasKey('doc', $this->errors(['doc' => 'cv.pdf'], ['doc' => 'required|file']));
        $this->assertArrayHasKey('doc', $this->errors([], ['doc' => 'required|file']));
        $this->assertSame([], $this->errors([], ['doc' => 'nullable|file']));
    }

    public function test_image_rule_checks_the_real_content(): void
    {
        $this->assertSame([], $this->errors(['avatar' => UploadedFile::fakeImage()], ['avatar' => 'image']));

        $disguised = UploadedFile::fake('avatar.png', '<?php echo 1;', 'image/png');
        $this->assertArrayHasKey('avatar', $this->errors(['avatar' => $disguised], ['avatar' => 'image']));
    }

    public function test_svg_is_not_accepted_as_an_image_by_default(): void
    {
        $svg = UploadedFile::fake('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'image/svg+xml');

        $this->assertArrayHasKey('logo', $this->errors(['logo' => $svg], ['logo' => 'image']));
        $this->assertSame([], $this->errors(['logo' => $svg], ['logo' => 'mimes:svg']), 'mimes:svg reste possible, explicitement.');
    }

    public function test_mimes_rule_compares_the_extension_deduced_from_the_content(): void
    {
        $png = UploadedFile::fakeImage('photo.jpg'); // nom trompeur : c'est un PNG

        $this->assertSame([], $this->errors(['photo' => $png], ['photo' => 'mimes:png,gif']));
        $this->assertArrayHasKey('photo', $this->errors(['photo' => $png], ['photo' => 'mimes:jpg,jpeg']));
    }

    public function test_mimetypes_rule_supports_wildcards(): void
    {
        $png = UploadedFile::fakeImage();

        $this->assertSame([], $this->errors(['f' => $png], ['f' => 'mimetypes:image/png']));
        $this->assertSame([], $this->errors(['f' => $png], ['f' => 'mimetypes:application/pdf,image/*']));
        $this->assertArrayHasKey('f', $this->errors(['f' => $png], ['f' => 'mimetypes:application/pdf']));
    }

    public function test_max_and_min_are_in_kilobytes_for_files(): void
    {
        $file = UploadedFile::fake('notes.txt', str_repeat('a', 3 * 1024)); // 3 Ko

        $this->assertSame([], $this->errors(['f' => $file], ['f' => 'file|max:3']));
        $this->assertSame([], $this->errors(['f' => $file], ['f' => 'file|min:3']));

        $errors = $this->errors(['f' => $file], ['f' => 'file|max:2']);
        $this->assertSame(['Le fichier f ne doit pas dépasser 2 Ko.'], $errors['f']);
        $this->assertArrayHasKey('f', $this->errors(['f' => $file], ['f' => 'file|between:4,10']));
    }

    public function test_dimensions_rule(): void
    {
        $image = UploadedFile::fakeImage('banniere.png', 300, 100);

        $this->assertSame([], $this->errors(['b' => $image], ['b' => 'image|dimensions:min_width=200,max_height=150']));
        $this->assertSame([], $this->errors(['b' => $image], ['b' => 'dimensions:width=300,height=100']));
        $this->assertArrayHasKey('b', $this->errors(['b' => $image], ['b' => 'dimensions:min_width=400']));
        $this->assertArrayHasKey('b', $this->errors(['b' => $image], ['b' => 'dimensions:max_height=50']));
        $this->assertArrayHasKey('b', $this->errors(['b' => UploadedFile::fake('x.txt', 'texte')], ['b' => 'dimensions:min_width=1']));
    }

    public function test_a_failed_upload_reports_its_real_reason(): void
    {
        $tooBig = new UploadedFile('', 'video.mp4', 'video/mp4', UPLOAD_ERR_INI_SIZE);

        $errors = $this->errors(['video' => $tooBig], ['video' => 'required|file|mimes:mp4']);

        $this->assertCount(1, $errors['video']);
        $this->assertStringContainsString('taille maximale', $errors['video'][0]);
    }

    public function test_multiple_files_can_be_validated_with_wildcards(): void
    {
        $photos = [UploadedFile::fakeImage(), UploadedFile::fake('notes.txt', 'texte')];

        $errors = $this->errors(['photos' => $photos], ['photos' => 'required|array', 'photos.*' => 'image']);

        $this->assertSame(['photos.1'], array_keys($errors));
    }

    public function test_validated_data_contains_the_file_object(): void
    {
        $avatar = UploadedFile::fakeImage();

        $data = Validator::make(['avatar' => $avatar, 'name' => 'Awa'], ['avatar' => 'required|image'])->validate();

        $this->assertSame(['avatar' => $avatar], $data);
    }
}
