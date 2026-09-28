<?php

namespace App\Support;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Project;
use Niang\Core\Auth;
use Niang\Core\Database\DB;
use Niang\Core\Database\QueryBuilder;
use Niang\Core\Tenancy;

/** L'organisation courante vue par l'utilisateur connecté : rôle, liens, limites du plan. */
class Team
{
    private const RANK = ['member' => 1, 'admin' => 2, 'owner' => 3];

    /** @return array<string, mixed> l'organisation courante (identifiée par IdentifyTenant) */
    public static function current(): array
    {
        return Tenancy::current() ?? throw new \LogicException('Aucune organisation courante.');
    }

    /** '/o/<slug>' + $path */
    public static function url(string $path = ''): string
    {
        return '/o/' . self::current()['slug'] . $path;
    }

    /** Rôle de l'utilisateur connecté dans l'organisation courante, ou null s'il n'en est pas membre. */
    public static function role(): ?string
    {
        $membership = Membership::query()->where('user_id', Auth::id())->first();

        return $membership['role'] ?? null;
    }

    /** Vrai si le rôle de l'utilisateur atteint $minimum (member < admin < owner). */
    public static function hasRole(string $minimum): bool
    {
        $role = self::role();

        return $role !== null && self::RANK[$role] >= (self::RANK[$minimum] ?? PHP_INT_MAX);
    }

    public static function ownerCount(): int
    {
        return Membership::query()->where('role', 'owner')->count();
    }

    /**
     * Organisations de l'utilisateur, avec son rôle dans chacune.
     *
     * @return list<array<string, mixed>>
     */
    public static function organizationsOf(int|string $userId): array
    {
        return (new QueryBuilder('tenants'))
            ->select('tenants.*', 'memberships.role')
            ->join('memberships', 'memberships.tenant_id', '=', 'tenants.id')
            ->where('memberships.user_id', $userId)
            ->orderBy('tenants.name')
            ->get();
    }

    /** Crée une organisation dont $userId est propriétaire. @return array<string, mixed> */
    public static function create(string $name, int|string $userId): array
    {
        return DB::transaction(function () use ($name, $userId): array {
            $id = Organization::forceCreate(['name' => $name, 'slug' => self::uniqueSlug($name), 'plan' => config('billing.default_plan', 'free')]);
            $organization = Organization::find($id);

            Tenancy::run($organization, fn () => Membership::forceCreate(['user_id' => $userId, 'role' => 'owner']));

            return $organization;
        });
    }

    /**
     * Branché sur la suppression d'un compte (config/site.php, before_account_deletion) : refuse si
     * l'utilisateur est le dernier propriétaire d'une organisation qui a d'autres membres ; sinon
     * retire ses appartenances et supprime les organisations dont il était le seul membre.
     */
    public static function beforeAccountDeletion(array $user): ?string
    {
        foreach (self::organizationsOf($user['id']) as $organization) {
            $refusal = Tenancy::run($organization, function () use ($user, $organization): ?string {
                $members = Membership::query()->count();

                if ($members === 1) {
                    Project::query()->delete();
                    DB::statement('DELETE FROM invitations WHERE tenant_id = ?', [$organization['id']]);
                    Membership::query()->delete();
                    Tenancy::central(fn () => Organization::forceDestroy($organization['id']));

                    return null;
                }

                if ($organization['role'] === 'owner' && self::ownerCount() === 1) {
                    return "Vous êtes le seul propriétaire de « {$organization['name']} » : nommez un autre propriétaire avant de supprimer votre compte.";
                }

                Membership::query()->where('user_id', $user['id'])->delete();

                return null;
            });

            if ($refusal !== null) {
                return $refusal;
            }
        }

        return null;
    }

    public static function slug(string $text): string
    {
        $text = strtr(mb_strtolower(trim($text)), [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'ae',
            'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i',
            'ñ' => 'n', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'œ' => 'oe', 'ø' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ý' => 'y', 'ß' => 'ss',
        ]);

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', $text), '-') ?: 'organisation';
    }

    private static function uniqueSlug(string $name): string
    {
        $base = self::slug($name);
        $slug = $base;

        for ($n = 2; Tenancy::find('slug', $slug) !== null; $n++) {
            $slug = "$base-$n";
        }

        return $slug;
    }
}
