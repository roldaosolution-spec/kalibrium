<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Domain;
use App\Models\Concerns\HasTenant;
use Database\Factories\StandardFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Standard extends Model implements AuditableContract
{
    /** @use HasFactory<StandardFactory> */
    use Auditable, HasFactory, HasTenant, HasUuids, SoftDeletes;

    protected $fillable = [
        'serial_number',
        'description',
        'certificate_number',
        'certificate_date',
        'validity_date',
        'domain',
        'drift_tolerance',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'domain' => Domain::class,
            'certificate_date' => 'date',
            'validity_date' => 'date',
            'drift_tolerance' => 'decimal:6',
        ];
    }

    /** AC-004-07: true when validity_date is in the past (today counts as valid). */
    public function isExpired(): bool
    {
        return $this->validity_date->isPast();
    }

    /** AC-004-07: gate check before selecting this standard in a calibration workflow. */
    public function isValidForUse(): bool
    {
        return ! $this->isExpired();
    }
}
