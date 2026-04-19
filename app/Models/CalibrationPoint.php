<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasTenant;
use Database\Factories\CalibrationPointFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibrationPoint extends Model
{
    /** @use HasFactory<CalibrationPointFactory> */
    use HasFactory, HasTenant, HasUuids;

    protected $fillable = [
        'calibration_id',
        'tenant_id',
        'nominal_value',
        'measured_value',
        'unit',
        'deviation',
        'uncertainty',
        'pass',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'nominal_value' => 'decimal:6',
            'measured_value' => 'decimal:6',
            'deviation' => 'decimal:6',
            'uncertainty' => 'decimal:6',
            'pass' => 'boolean',
        ];
    }

    /** @return BelongsTo<Calibration, $this> */
    public function calibration(): BelongsTo
    {
        return $this->belongsTo(Calibration::class);
    }
}
