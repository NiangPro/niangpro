<?php

namespace Tests\Feature;

use Niang\Core\Http\Request;
use Niang\Core\Http\Response;
use Niang\Core\Http\UploadedFile;
use Niang\Core\Storage;
use Niang\Core\Testing\TestCase;
use Niang\Core\Validation\FormRequest;
use Niang\Core\Validation\Validator;

class FileUploadTest extends TestCase
{
    private const DIRECTORY = 'feature-uploads';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->router->post('/test-upload', function (Request $request): Response {
            $data = Validator::make($request->allWithFiles(), [
                'name' => 'required|string',
                'avatar' => 'required|image|max:500',
            ])->validate();

            return Response::json(['path' => $data['avatar']->store(self::DIRECTORY), 'name' => $data['name']]);
        });

        $this->app->router->post('/test-upload-form-request', function (FileUploadTestRequest $request): Response {
            return Response::json(['path' => $request->file('document')->store(self::DIRECTORY)]);
        });
    }

    protected function tearDown(): void
    {
        foreach (glob(Storage::path(self::DIRECTORY) . '/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir(Storage::path(self::DIRECTORY));

        parent::tearDown();
    }

    public function test_a_valid_upload_is_validated_and_stored(): void
    {
        $response = $this->post('/test-upload', ['name' => 'Awa', 'avatar' => UploadedFile::fakeImage('moi.png', 64, 64)]);

        $response->assertOk();
        $path = $response->json()['path'];
        $this->assertStringStartsWith(self::DIRECTORY . '/', $path);
        $this->assertStringEndsWith('.png', $path);
        $this->assertTrue(Storage::exists($path));
    }

    public function test_a_form_request_receives_the_uploaded_files(): void
    {
        $response = $this->post('/test-upload-form-request', ['document' => UploadedFile::fake('cv.pdf', "%PDF-1.4\n%fin")]);

        $response->assertOk();
        $this->assertStringEndsWith('.pdf', $response->json()['path']);
    }

    public function test_an_invalid_upload_is_rejected_with_a_422_for_json_clients(): void
    {
        $response = $this->post(
            '/test-upload',
            ['name' => 'Awa', 'avatar' => UploadedFile::fake('moi.png', '<?php echo 1;', 'image/png')],
            ['Accept' => 'application/json']
        );

        $response->assertStatus(422);
        $this->assertArrayHasKey('avatar', $response->json()['errors']);
    }

    public function test_old_input_flashed_after_a_failed_upload_never_contains_the_file(): void
    {
        $this->post(
            '/test-upload',
            ['name' => 'Awa', 'avatar' => UploadedFile::fake('moi.png', 'pas une image')],
            ['Referer' => '/profil']
        )->assertRedirect('/profil');

        // Flashé pour la requête suivante : pas encore lisible via Session::getFlash().
        $old = $_SESSION['_flash_new']['old'] ?? [];
        $this->assertSame('Awa', $old['name'] ?? null);
        $this->assertArrayNotHasKey('avatar', $old);
    }
}

class FileUploadTestRequest extends FormRequest
{
    public function rules(): array
    {
        return ['document' => 'required|file|mimes:pdf|max:2048'];
    }
}
