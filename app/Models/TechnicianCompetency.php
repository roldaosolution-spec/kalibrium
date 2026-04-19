<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Domain;
use App\Models\Concerns\HasTenant;
use Database\Factories\TechnicianCompetencyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TechnicianCompetency extends Model implements AuditableContract
{
    /** @use HasFactory<TechnicianCompetencyFactory> */
    use Auditable, HasFactory, HasTenant, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'domain',
        'qualified_at',
        'expires_at',
        'certificate_ref',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'domain' => Domain::class,
            'qualified_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValidForDomain(Domain $domain): bool
    {
        return $this->domain === $domain && ! $this->isExpired();
    }
}
