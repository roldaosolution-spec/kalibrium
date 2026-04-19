<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake('pt_BR')->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'cnpj' => fake('pt_BR')->cnpj(),
            'status' => 'active',
            'settings' => null,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'suspended']);
    }

    public function withSettings(array $settings): static
    {
        return $this->state(fn (array $attributes) => ['settings' => $settings]);
    }
}
