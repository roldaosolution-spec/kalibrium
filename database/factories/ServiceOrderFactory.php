<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceOrderMode;
use App\Enums\ServiceOrderStatus;
use App\Models\Client;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceOrder> */
class ServiceOrderFactory extends Factory
{
    protected $model = ServiceOrder::class;

    public function definition(): array
    {
        return [
            'mode' => fake()->randomElement(ServiceOrderMode::cases())->value,
            'status' => ServiceOrderStatus::Draft->value,
            'sla_date' => fake()->dateTimeBetween('+7 days', '+60 days')->format('Y-m-d'),
            'notes' => fake('pt_BR')->optional(0.6)->sentence(10),
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

    public function withTechnician(User $technician): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_technician_id' => $technician->id,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceOrderStatus::Open->value,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceOrderStatus::InProgress->value,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ServiceOrderStatus::Completed->value,
        ]);
    }
}
