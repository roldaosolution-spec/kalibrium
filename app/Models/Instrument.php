<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Domain;
use App\Models\Concerns\HasTenant;
use Database\Factories\InstrumentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Instrument extends Model implements AuditableContract
{
    /** @use HasFactory<InstrumentFactory> */
    use Auditable, HasFactory, HasTenant, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'serial_number',
        'type',
        'description',
        'range_min',
        'range_max',
        'resolution',
        'domain',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'domain' => Domain::class,
            'range_min' => 'decimal:6',
            'range_max' => 'decimal:6',
            'resolution' => 'decimal:6',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
