<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Project;
use App\Models\User;
use App\Support\Team;
use Niang\Core\Auth;
use Niang\Core\Csrf;
use Niang\Core\Hash;
use Niang\Core\Mail;
use Niang\Core\RateLimiter;
use Niang\Core\Tenancy;
use Niang\Core\Testing\RefreshDatabase;
use Niang\Core\Testing\TestCase;

/** Livré avec le starter « saas » : organisations isolées, rôles, invitations, plans et limites. */
class SaasStarterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::reset();
        Mail::fake();
    }

    private function user(string $email): array
    {
        $id = User::create(['name' => ucfirst(explode('@', $email)[0]), 'email' => $email, 'password' => Hash::make('secret123')]);

        return User::find($id);
    }

    private function as(array $user): void
    {
        Auth::login($user);
    }

    /** @return array<string, mixed> */
    private function organization(string $name, array $owner): array
    {
        return Team::create($name, $owner['id']);
    }

    private function join(array $organization, array $user, string $role): void
    {
        Tenancy::run($organization, fn () => Membership::forceCreate(['user_id' => $user['id'], 'role' => $role]));
    }

    private function post_(string $uri, array $data = []): \Niang\Core\Testing\TestResponse
    {
        return $this->post($uri, ['_token' => Csrf::token()] + $data);
    }

    public function test_the_home_page_shows_the_plans(): void
    {
        $this->get('/')->assertOk()->assertSee('Gratuit')->assertSee('Pro')->assertSee('href="/register"');
    }

    public function test_a_new_member_creates_an_organization_and_becomes_its_owner(): void
    {
        $this->as($this->user('awa@example.com'));

        $this->get('/organisations')->assertOk()->assertSee('Créez votre première organisation');
        $this->post_('/organisations', ['name' => 'Téranga Studio'])->assertRedirect('/o/teranga-studio');

        $this->get('/o/teranga-studio')->assertOk()->assertSee('Téranga Studio')->assertSee('Propriétaire');
        $this->get('/organisations')->assertRedirect('/o/teranga-studio');
    }

    public function test_slugs_stay_unique(): void
    {
        $awa = $this->user('awa@example.com');

        $this->assertSame('atelier', $this->organization('Atelier', $awa)['slug']);
        $this->assertSame('atelier-2', $this->organization('Atelier', $awa)['slug']);
    }

    public function test_organizations_never_see_each_others_projects(): void
    {
        $awa = $this->user('awa@example.com');
        $moussa = $this->user('moussa@example.com');
        $this->organization('Dakar', $awa);
        $this->organization('Thies', $moussa);

        $this->as($awa);
        $this->post_('/o/dakar/projets', ['name' => 'Site vitrine'])->assertRedirect('/o/dakar/projets');

        $this->as($moussa);
        $this->get('/o/thies/projets')->assertOk()->assertDontSee('Site vitrine');
        $this->get('/o/dakar')->assertStatus(404);
        $this->get('/o/dakar/projets')->assertStatus(404);
        $this->post_('/o/dakar/projets', ['name' => 'Intrus'])->assertStatus(404);

        $this->assertSame(['Site vitrine'], Tenancy::central(fn () => Project::query()->pluck('name')));
    }

    public function test_members_read_but_only_admins_manage(): void
    {
        $owner = $this->user('awa@example.com');
        $member = $this->user('moussa@example.com');
        $organization = $this->organization('Dakar', $owner);
        $this->join($organization, $member, 'member');
        $projectId = Tenancy::run($organization, fn () => Project::create(['name' => 'P1']));

        $this->as($member);
        $this->get('/o/dakar/membres')->assertOk()->assertDontSee('Envoyer l\'invitation');
        $this->post_('/o/dakar/membres/invitations', ['email' => 'x@example.com', 'role' => 'member'])->assertStatus(403);
        $this->post_("/o/dakar/projets/$projectId/supprimer")->assertStatus(403);
        $this->get('/o/dakar/abonnement')->assertStatus(403);
    }

    public function test_an_invitation_is_emailed_and_accepted_by_the_invited_address_only(): void
    {
        $owner = $this->user('awa@example.com');
        $this->organization('Dakar', $owner);
        $this->as($owner);

        $this->post_('/o/dakar/membres/invitations', ['email' => 'Fatou@Example.com', 'role' => 'admin'])->assertRedirect('/o/dakar/membres');
        $this->assertCount(1, Mail::sent());
        $this->assertSame('fatou@example.com', Mail::sent()[0]['to']);
        $this->assertSame(1, preg_match('#/invitations/([a-f0-9]{64})#', Mail::sent()[0]['mailable']->body(), $m));
        $token = $m[1] ?? '';

        $this->as($this->user('intrus@example.com'));
        $this->get("/invitations/$token")->assertOk()->assertSee('destinée à');
        $this->post_("/invitations/$token")->assertRedirect("/invitations/$token");

        $this->as($this->user('fatou@example.com'));
        $this->post_("/invitations/$token")->assertRedirect('/o/dakar');
        $this->get('/o/dakar/membres')->assertOk()->assertSee('Changer');   // administratrice : peut changer les rôles

        $this->get("/invitations/$token")->assertStatus(404);   // à usage unique
    }

    public function test_plan_limits_and_upgrading(): void
    {
        $owner = $this->user('awa@example.com');
        $organization = $this->organization('Dakar', $owner);
        $this->as($owner);

        foreach (['Alpha', 'Bravo', 'Charlie'] as $name) {
            $this->post_('/o/dakar/projets', ['name' => $name]);
        }

        $this->post_('/o/dakar/projets', ['name' => 'Delta']);
        $this->assertSame(3, Tenancy::run($organization, fn () => Project::query()->count()));
        $this->get('/o/dakar/projets')->assertSee('Votre plan permet 3 projets');

        $this->post_('/o/dakar/abonnement', ['plan' => 'pro'])->assertRedirect(url('/o/dakar/abonnement'));
        $this->post_('/o/dakar/projets', ['name' => 'Delta']);
        $this->assertSame(4, Tenancy::run($organization, fn () => Project::query()->count()));

        $this->post_('/o/dakar/abonnement/resilier');
        $this->assertSame('free', Tenancy::find('id', $organization['id'])['plan']);
    }

    public function test_only_an_owner_changes_the_subscription(): void
    {
        $owner = $this->user('awa@example.com');
        $admin = $this->user('moussa@example.com');
        $organization = $this->organization('Dakar', $owner);
        $this->join($organization, $admin, 'admin');

        $this->as($admin);
        $this->get('/o/dakar/abonnement')->assertOk()->assertSee('Seul un propriétaire');
        $this->post_('/o/dakar/abonnement', ['plan' => 'pro'])->assertStatus(403);
    }

    public function test_the_last_owner_cannot_be_demoted_removed_or_leave(): void
    {
        $owner = $this->user('awa@example.com');
        $member = $this->user('moussa@example.com');
        $organization = $this->organization('Dakar', $owner);
        $this->join($organization, $member, 'member');
        $ownership = Tenancy::run($organization, fn () => Membership::query()->where('user_id', $owner['id'])->first());

        $this->as($owner);
        $this->post_("/o/dakar/membres/{$ownership['id']}/role", ['role' => 'member']);
        $this->post_('/o/dakar/membres/quitter');

        $this->assertSame('owner', Tenancy::run($organization, fn () => Membership::find($ownership['id'])['role']));
    }

    public function test_deleting_an_account_respects_ownership(): void
    {
        $owner = $this->user('awa@example.com');
        $member = $this->user('moussa@example.com');
        $shared = $this->organization('Partagée', $owner);
        $this->organization('Solo', $owner);
        $this->join($shared, $member, 'member');

        // Dernier propriétaire d'une organisation qui a d'autres membres : refusé, rien n'est supprimé.
        $this->as($owner);
        $this->post_('/compte/supprimer', ['delete_password' => 'secret123'])->assertRedirect('/compte');
        $this->assertNotNull(User::find($owner['id']));
        $this->assertNotNull(Tenancy::find('slug', 'solo'));

        // Un simple membre : ses appartenances sont retirées avec lui.
        $this->as($member);
        $this->post_('/compte/supprimer', ['delete_password' => 'secret123'])->assertRedirect('/');
        $this->assertNull(User::find($member['id']));

        // Désormais seul membre de ses deux organisations : elles disparaissent avec le compte.
        $this->as($owner);
        $this->post_('/compte/supprimer', ['delete_password' => 'secret123'])->assertRedirect('/');
        $this->assertNull(Tenancy::find('slug', 'partagee'));
        $this->assertNull(Tenancy::find('slug', 'solo'));
    }

    public function test_an_invitation_cannot_exceed_the_member_limit(): void
    {
        $owner = $this->user('awa@example.com');
        $this->organization('Dakar', $owner);
        $this->as($owner);

        // Deux invitations envoyées tant qu'il restait une place ; la seconde acceptée dépasserait la limite (2).
        $this->post_('/o/dakar/membres/invitations', ['email' => 'fatou@example.com', 'role' => 'member']);
        $this->post_('/o/dakar/membres/invitations', ['email' => 'ibou@example.com', 'role' => 'member']);
        $tokens = array_map(fn ($mail) => preg_match('#/invitations/([a-f0-9]{64})#', $mail['mailable']->body(), $m) ? $m[1] : '', Mail::sent());

        $this->as($this->user('fatou@example.com'));
        $this->post_("/invitations/{$tokens[0]}")->assertRedirect('/o/dakar');

        $this->as($this->user('ibou@example.com'));
        $this->post_("/invitations/{$tokens[1]}")->assertRedirect("/invitations/{$tokens[1]}");
        $this->get('/o/dakar')->assertStatus(404);
    }
}
