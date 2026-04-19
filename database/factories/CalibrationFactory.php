<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CalibrationStatus;
use App\Models\Calibration;
use App\Models\Instrument;
use App\Models\Procedure;
use App\Models\ServiceOrder;
use App\Models\Standard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Calibration> */
class CalibrationFactory extends Factory
{
    protected $model = Calibration::class;

    public function definition(): array
    {
        return [
            'status' => CalibrationStatus::Draft->value,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function forServiceOrder(ServiceOrder $serviceOrder): static
    {
        return $this->state(fn (array $attributes) => [
            'service_order_id' => $serviceOrder->id,
            'tenant_id' => $serviceOrder->tenant_id,
        ]);
    }

    public function forInstrument(Instrument $instrument): static
    {
        return $this->state(fn (array $attributes) => [
            'instrument_id' => $instrument->id,
        ]);
    }

    public function withStandard(Standard $standard): static
    {
        return $this->state(fn (array $attributes) => [
            'standard_id' => $standard->id,
        ]);
    }

    public function withProcedure(Procedure $procedure): static
    {
        return $this->state(fn (array $attributes) => [
            'procedure_id' => $procedure->id,
        ]);
    }

    public function withExecutor(User $executor): static
    {
        return $this->state(fn (array $attributes) => [
            'executor_id' => $executor->id,
            'status' => CalibrationStatus::InProgress->value,
            'started_at' => now(),
        ]);
    }

    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CalibrationStatus::PendingReview->value,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CalibrationStatus::Approved->value,
            'completed_at' => now(),
        ]);
    }
}
