<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Domain;
use App\Models\TechnicianCompetency;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TechnicianCompetency> */
class TechnicianCompetencyFactory extends Factory
{
    protected $model = TechnicianCompetency::class;

    public function definition(): array
    {
        $domain = fake()->randomElement(Domain::cases());
        $qualifiedAt = fake()->dateTimeBetween('-3 years', '-6 months');
        $expiresAt = fake()->dateTimeBetween('now', '+2 years');

        return [
            'domain' => $domain->value,
            'qualified_at' => $qualifiedAt->format('Y-m-d'),
            'expires_at' => $expiresAt->format('Y-m-d'),
            'certificate_ref' => strtoupper(fake()->bothify('COMP-####/##')),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forDomain(Domain $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain->value,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
        ]);
    }
}
