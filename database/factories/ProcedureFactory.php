<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Domain;
use App\Models\Procedure;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Procedure> */
class ProcedureFactory extends Factory
{
    protected $model = Procedure::class;

    public function definition(): array
    {
        $domain = fake()->randomElement(Domain::cases());

        return [
            'code' => strtoupper(fake()->bothify('PROC-???-##')),
            'title' => 'Procedimento de Calibração — ' . fake('pt_BR')->words(3, true),
            'domain' => $domain->value,
            'revision' => fake()->randomElement(['00', '01', '02', 'A', 'B']),
            'steps' => [
                ['order' => 1, 'description' => 'Verificar condições ambientais'],
                ['order' => 2, 'description' => 'Preparar o instrumento'],
                ['order' => 3, 'description' => 'Executar medições'],
                ['order' => 4, 'description' => 'Registrar resultados'],
            ],
            'uncertainty_formula' => 'U = k * sqrt(u1^2 + u2^2)',
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function forDomain(Domain $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain->value,
        ]);
    }
}
