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
        'tenant_id',
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

    public function isExpired(): bool
    {
        return $this->validity_date->isPast();
    }

    public function isValidForUse(): bool
    {
        return ! $this->isExpired();
    }
}
