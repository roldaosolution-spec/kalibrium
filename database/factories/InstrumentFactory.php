<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Domain;
use App\Models\Client;
use App\Models\Instrument;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Instrument> */
class InstrumentFactory extends Factory
{
    protected $model = Instrument::class;

    public function definition(): array
    {
        $domain = fake()->randomElement(Domain::cases());
        $rangeMin = fake()->randomFloat(2, 0, 100);
        $rangeMax = fake()->randomFloat(2, $rangeMin + 1, 1000);

        return [
            'serial_number' => strtoupper(fake()->bothify('??###-####')),
            'type' => fake()->randomElement(['Paquímetro', 'Micrômetro', 'Manômetro', 'Balança', 'Termômetro', 'Régua']),
            'description' => fake('pt_BR')->sentence(6),
            'range_min' => $rangeMin,
            'range_max' => $rangeMax,
            'resolution' => fake()->randomFloat(4, 0.0001, 0.1),
            'domain' => $domain->value,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
        ]);
    }

    public function forDomain(Domain $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain->value,
        ]);
    }
}
