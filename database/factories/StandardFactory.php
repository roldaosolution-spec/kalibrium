<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Domain;
use App\Models\Standard;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Standard> */
class StandardFactory extends Factory
{
    protected $model = Standard::class;

    public function definition(): array
    {
        $domain = fake()->randomElement(Domain::cases());
        $certDate = fake()->dateTimeBetween('-2 years', '-6 months');
        $validityDate = fake()->dateTimeBetween('now', '+2 years');

        return [
            'serial_number' => strtoupper(fake()->bothify('PAD-##-????')),
            'description' => fake('pt_BR')->sentence(8),
            'certificate_number' => strtoupper(fake()->bothify('CERT-####/###')),
            'certificate_date' => $certDate->format('Y-m-d'),
            'validity_date' => $validityDate->format('Y-m-d'),
            'domain' => $domain->value,
            'drift_tolerance' => fake()->randomFloat(6, 0.000001, 0.01),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'validity_date' => fake()->dateTimeBetween('-2 years', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function forDomain(Domain $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain->value,
        ]);
    }
}
