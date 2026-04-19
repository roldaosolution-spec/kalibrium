<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalibrationStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\Concerns\AuthorizationHelpers;
use App\Models\Concerns\GeneratesAnnualSequenceNumber;
use App\Models\Concerns\HasTenant;
use Carbon\Carbon;
use Database\Factories\CalibrationFactory;
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
 * @property CalibrationStatus $status
 * @property string|null $executor_id
 * @property string|null $verifier_id
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 */
class Calibration extends Model implements AuditableContract
{
    /** @use HasFactory<CalibrationFactory> */
    use Auditable, AuthorizationHelpers, GeneratesAnnualSequenceNumber, HasFactory, HasTenant, HasUuids, SoftDeletes;

    protected $fillable = [
        'service_order_id',
        'instrument_id',
        'standard_id',
        'procedure_id',
        'executor_id',
        'verifier_id',
        'status',
        'started_at',
        'completed_at',
        'certificate_number',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => CalibrationStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->status)) {
                $model->status = CalibrationStatus::Draft;
            }
        });
    }

    public function changeStatus(CalibrationStatus $newStatus): void
    {
        $this->assertTransitionAllowed($newStatus);

        DB::transaction(function () use ($newStatus): void {
            $this->status = $newStatus;
            $this->save();
        });
    }

    public function start(User $executor): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::InProgress);
        $this->loadMissing('instrument');
        $this->assertTechnicianCompetency($executor, $this->instrument->domain);
        $this->assertStandardValid();

        DB::transaction(function () use ($executor): void {
            $this->executor_id = $executor->id;
            $this->status = CalibrationStatus::InProgress;
            $this->started_at = now();
            $this->save();
        });
    }

    public function submitForReview(): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::PendingReview);

        DB::transaction(function (): void {
            $this->status = CalibrationStatus::PendingReview;
            $this->save();
        });
    }

    public function approve(User $verifier): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::Approved);

        if ($verifier->id === $this->executor_id) {
            throw new InvalidTransitionException(
                'Verificador não pode ser o mesmo que o executor (dual sign-off ISO 17025)',
            );
        }

        $this->loadMissing('instrument');
        $this->assertTechnicianCompetency($verifier, $this->instrument->domain);

        DB::transaction(function () use ($verifier): void {
            $this->verifier_id = $verifier->id;
            $this->status = CalibrationStatus::Approved;
            $this->completed_at = now();
            $this->save();
        });
    }

    public function issue(): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::Issued);

        DB::transaction(function (): void {
            $this->certificate_number = $this->generateCertificateNumber();
            $this->status = CalibrationStatus::Issued;
            $this->save();
        });
    }

    public function cancel(): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::Cancelled);

        DB::transaction(function (): void {
            $this->status = CalibrationStatus::Cancelled;
            $this->save();
        });
    }

    private function generateCertificateNumber(): string
    {
        $year = (int) date('Y');
        $last = static::withoutGlobalScopes()
            ->select('certificate_number')
            ->where('tenant_id', $this->tenant_id)
            ->whereBetween('created_at', ["{$year}-01-01", ($year + 1) . '-01-01'])
            ->whereNotNull('certificate_number')
            ->orderByDesc('certificate_number')
            ->lockForUpdate()
            ->first()?->certificate_number;

        $seq = $last !== null ? ((int) substr($last, -4)) + 1 : 1;

        return 'CERT-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function assertStandardValid(): void
    {
        $this->loadMissing('standard');

        if ($this->standard !== null && ! $this->standard->isValidForUse()) {
            throw new InvalidTransitionException(
                'Padrão de calibração com certificado vencido — calibração bloqueada',
            );
        }
    }

    /** @return BelongsTo<ServiceOrder, $this> */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    /** @return BelongsTo<Instrument, $this> */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    /** @return BelongsTo<Standard, $this> */
    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    /** @return BelongsTo<Procedure, $this> */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /** @return BelongsTo<User, $this> */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    /** @return HasMany<CalibrationPoint, $this> */
    public function points(): HasMany
    {
        return $this->hasMany(CalibrationPoint::class);
    }
}
