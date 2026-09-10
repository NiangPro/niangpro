<?php

namespace Tests\Feature;

use Niang\Core\Csrf;
use Niang\Core\Testing\TestCase;

class ContactTest extends TestCase
{
    public function test_invalid_submission_redirects_back_with_errors(): void
    {
        $this->post('/contact', ['_token' => Csrf::token(), 'name' => 'A'])->assertRedirect();
    }

    public function test_valid_submission_redirects_to_contact_page(): void
    {
        $this->post('/contact', [
            '_token' => Csrf::token(),
            'name' => 'Awa',
            'email' => 'awa@example.com',
            'message' => 'Un message suffisamment long pour être valide',
        ])->assertRedirect('/contact');
    }

    public function test_submission_without_csrf_token_is_rejected(): void
    {
        $this->post('/contact', ['name' => 'Awa'])->assertStatus(419);
    }
}
