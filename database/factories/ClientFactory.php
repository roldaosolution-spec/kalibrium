<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $cnpj = $this->fakeCnpj();

        return [
            'name' => fake('pt_BR')->company(),
            'cnpj' => $cnpj,
            'address' => fake('pt_BR')->address(),
            'phone' => fake('pt_BR')->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'contact_person' => fake('pt_BR')->name(),
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    private function fakeCnpj(): string
    {
        $n = [];

        for ($i = 0; $i < 12; $i++) {
            $n[] = $this->faker->numberBetween(0, 9);
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += $n[$i] * $weights1[$i];
        }
        $d1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);
        $n[] = $d1;

        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;

        for ($i = 0; $i < 13; $i++) {
            $sum += $n[$i] * $weights2[$i];
        }
        $d2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);
        $n[] = $d2;

        return sprintf(
            '%d%d.%d%d%d.%d%d%d/%d%d%d%d-%d%d',
            $n[0], $n[1], $n[2], $n[3], $n[4], $n[5], $n[6], $n[7],
            $n[8], $n[9], $n[10], $n[11], $n[12], $n[13],
        );
    }
}
