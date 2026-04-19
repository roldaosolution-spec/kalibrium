<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Domain;
use App\Models\Concerns\HasTenant;
use Database\Factories\ProcedureFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Procedure extends Model implements AuditableContract
{
    /** @use HasFactory<ProcedureFactory> */
    use Auditable, HasFactory, HasTenant, HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'domain',
        'revision',
        'steps',
        'uncertainty_formula',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'domain' => Domain::class,
            'steps' => 'array',
        ];
    }
}
