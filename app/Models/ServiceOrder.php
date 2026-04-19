<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceOrderMode;
use App\Enums\ServiceOrderStatus;
use App\Models\Concerns\AuthorizationHelpers;
use App\Models\Concerns\GeneratesAnnualSequenceNumber;
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

/**
 * @property ServiceOrderStatus $status
 * @property ServiceOrderMode $mode
 */
class ServiceOrder extends Model implements AuditableContract
{
    /** @use HasFactory<ServiceOrderFactory> */
    use Auditable, AuthorizationHelpers, GeneratesAnnualSequenceNumber, HasFactory, HasTenant, HasUuids, SoftDeletes;

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
                // Wrap in a transaction so lockForUpdate() inside nextNumber() is effective.
                DB::transaction(function () use ($model): void {
                    $model->number = static::nextNumber($model->tenant_id ?? '');
                });
            }
        });
    }

    public static function nextNumber(string $tenantId): string
    {
        return static::generateAnnualSequenceNumber('OS-', 'number', $tenantId);
    }

    public function changeStatus(ServiceOrderStatus $newStatus): void
    {
        $this->assertTransitionAllowed($newStatus);

        DB::transaction(function () use ($newStatus): void {
            $this->status = $newStatus;
            $this->save();
        });
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
