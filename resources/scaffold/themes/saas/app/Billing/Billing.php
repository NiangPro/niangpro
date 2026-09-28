<?php

namespace App\Billing;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Project;
use Niang\Core\Env;
use Niang\Core\Tenancy;

/** Plans, limites et état de l'abonnement d'une organisation. */
class Billing
{
    public static function provider(): BillingProvider
    {
        $class = (string) config('billing.provider', FakeBillingProvider::class);

        if ($class === FakeBillingProvider::class && Env::get('APP_ENV') === 'production') {
            throw new \RuntimeException('BILLING_PROVIDER « fake » en production : configurez un vrai prestataire de paiement.');
        }

        $provider = new $class();

        if (!$provider instanceof BillingProvider) {
            throw new \RuntimeException("$class doit implémenter App\\Billing\\BillingProvider.");
        }

        return $provider;
    }

    /** @return array<string, array{name: string, price: int|float, limits: array<string, int|null>}> */
    public static function plans(): array
    {
        return (array) config('billing.plans', []);
    }

    /** @param array<string, mixed> $organization */
    public static function plan(array $organization): array
    {
        $plans = self::plans();

        return $plans[$organization['plan'] ?? ''] ?? $plans[(string) config('billing.default_plan', 'free')];
    }

    /** Limite du plan courant pour $resource ('projects', 'members'), null = illimité. */
    public static function limit(string $resource): ?int
    {
        $limit = self::plan(Tenancy::current() ?? [])['limits'][$resource] ?? null;

        return $limit === null ? null : (int) $limit;
    }

    /** Vrai si l'organisation courante peut encore ajouter un élément de $resource. */
    public static function allows(string $resource): bool
    {
        $limit = self::limit($resource);

        if ($limit === null) {
            return true;
        }

        $used = match ($resource) {
            'projects' => Project::query()->count(),
            'members' => Membership::query()->count(),
            default => 0,
        };

        return $used < $limit;
    }

    /**
     * Enregistre un changement d'abonnement (appelé par le prestataire, ou par son webhook).
     *
     * @param array{plan?: string, subscription_status?: ?string, subscription_ends_at?: ?string, billing_reference?: ?string} $changes
     */
    public static function apply(int|string $organizationId, array $changes): void
    {
        if (isset($changes['plan']) && !array_key_exists($changes['plan'], self::plans())) {
            throw new \InvalidArgumentException("Plan inconnu : « {$changes['plan']} ».");
        }

        Tenancy::central(fn () => Organization::forceUpdate($organizationId, $changes));
    }
}
