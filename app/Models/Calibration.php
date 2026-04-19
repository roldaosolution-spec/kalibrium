<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalibrationStatus;
use App\Enums\Domain;
use App\Models\Concerns\HasTenant;
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

class Calibration extends Model implements AuditableContract
{
    /** @use HasFactory<CalibrationFactory> */
    use Auditable, HasFactory, HasTenant, HasUuids, SoftDeletes;

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
        if (! $this->status->canTransitionTo($newStatus)) {
            throw new \LogicException(
                "Transição inválida: {$this->status->value} → {$newStatus->value}",
            );
        }

        $this->status = $newStatus;
        $this->save();
    }

    public function start(User $executor): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::InProgress);
        $this->assertTechnicianCompetency($executor);
        $this->assertStandardValid();

        $this->executor_id = $executor->id;
        $this->status = CalibrationStatus::InProgress;
        $this->started_at = now();
        $this->save();
    }

    public function submitForReview(): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::PendingReview);
        $this->status = CalibrationStatus::PendingReview;
        $this->save();
    }

    public function approve(User $verifier): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::Approved);

        if ($verifier->id === $this->executor_id) {
            throw new \LogicException(
                'Verificador não pode ser o mesmo que o executor (dual sign-off ISO 17025)',
            );
        }

        $this->assertTechnicianCompetency($verifier);

        $this->verifier_id = $verifier->id;
        $this->status = CalibrationStatus::Approved;
        $this->completed_at = now();
        $this->save();
    }

    public function issue(): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::Issued);

        DB::transaction(function (): void {
            $this->certificate_number = $this->nextCertificateNumber();
            $this->status = CalibrationStatus::Issued;
            $this->save();
        });
    }

    public function cancel(): void
    {
        $this->assertTransitionAllowed(CalibrationStatus::Cancelled);
        $this->status = CalibrationStatus::Cancelled;
        $this->save();
    }

    private function nextCertificateNumber(): string
    {
        $year = (int) date('Y');
        // PostgreSQL does not allow FOR UPDATE with aggregate functions.
        $last = static::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant_id)
            ->whereBetween('created_at', ["{$year}-01-01", ($year + 1) . '-01-01'])
            ->whereNotNull('certificate_number')
            ->max('certificate_number');

        $seq = $last !== null ? ((int) substr($last, -4)) + 1 : 1;

        return 'CERT-' . $year . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function assertTransitionAllowed(CalibrationStatus $newStatus): void
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            throw new \LogicException(
                "Transição inválida: {$this->status->value} → {$newStatus->value}",
            );
        }
    }

    private function assertTechnicianCompetency(User $user): void
    {
        $this->loadMissing('instrument');
        $domain = $this->instrument->domain;

        // tenant_id scopes the lookup correctly and hits the (tenant_id, user_id, domain) unique index
        $hasCompetency = TechnicianCompetency::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant_id)
            ->where('user_id', $user->id)
            ->where('domain', $domain->value)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

        if (! $hasCompetency) {
            throw new \LogicException(
                "Técnico não possui competência válida para o domínio: {$domain->label()}",
            );
        }
    }

    private function assertStandardValid(): void
    {
        $this->loadMissing('standard');

        if ($this->standard !== null && ! $this->standard->isValidForUse()) {
            throw new \LogicException(
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
