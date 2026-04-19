<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Calibration;
use App\Models\CalibrationPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CalibrationPoint> */
class CalibrationPointFactory extends Factory
{
    protected $model = CalibrationPoint::class;

    public function definition(): array
    {
        $nominal = fake()->randomFloat(3, 0.1, 1000.0);
        $deviation = fake()->randomFloat(6, -0.01, 0.01);
        $measured = round($nominal + $deviation, 6);
        $uncertainty = abs(fake()->randomFloat(6, 0.001, 0.005));

        return [
            'nominal_value' => $nominal,
            'measured_value' => $measured,
            'unit' => fake()->randomElement(['mm', 'kPa', 'g', '°C', 'mV']),
            'deviation' => $deviation,
            'uncertainty' => $uncertainty,
            'pass' => abs($deviation) <= $uncertainty * 2,
        ];
    }

    public function forCalibration(Calibration $calibration): static
    {
        return $this->state(fn (array $attributes) => [
            'calibration_id' => $calibration->id,
            'tenant_id' => $calibration->tenant_id,
        ]);
    }

    public function passing(): static
    {
        return $this->state(function (array $attributes) {
            $nominal = fake()->randomFloat(3, 0.1, 100.0);

            return [
                'nominal_value' => $nominal,
                'measured_value' => $nominal + 0.001,
                'deviation' => 0.001,
                'uncertainty' => 0.01,
                'pass' => true,
            ];
        });
    }

    public function failing(): static
    {
        return $this->state(function (array $attributes) {
            $nominal = fake()->randomFloat(3, 0.1, 100.0);

            return [
                'nominal_value' => $nominal,
                'measured_value' => $nominal + 1.0,
                'deviation' => 1.0,
                'uncertainty' => 0.001,
                'pass' => false,
            ];
        });
    }
}
