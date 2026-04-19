<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceOrderMode;
use App\Enums\ServiceOrderStatus;
use App\Models\Concerns\HasTenant;
use Database\Factories\ServiceOrderFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ServiceOrder extends Model implements AuditableContract
{
    /** @use HasFactory<ServiceOrderFactory> */
    use Auditable, HasFactory, HasTenant, HasUuids, SoftDeletes;

    protected $fillable = [
        'number',
        'client_id',
        'mode',
        'status',
        'sla_date',
        'assigned_technician_id',
        'notes',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'mode' => ServiceOrderMode::class,
            'status' => ServiceOrderStatus::class,
            'sla_date' => 'date',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->number)) {
                $model->number = static::nextNumber($model->tenant_id ?? '');
            }
        });
    }

    public static function nextNumber(string $tenantId): string
    {
        $year = (int) date('Y');
        // whereBetween lets the DB use an index on created_at; whereYear() cannot
        $count = static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', ["{$year}-01-01", ($year + 1) . '-01-01'])
            ->count();

        return 'OS-' . $year . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function changeStatus(ServiceOrderStatus $newStatus): void
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            throw new \LogicException(
                "Transição inválida: {$this->status->value} → {$newStatus->value}",
            );
        }

        $this->status = $newStatus;
        $this->save();
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    /** @return HasMany<Calibration, $this> */
    public function calibrations(): HasMany
    {
        return $this->hasMany(Calibration::class);
    }
}
