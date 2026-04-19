<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasTenant;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Client extends Model implements AuditableContract
{
    /** @use HasFactory<ClientFactory> */
    use Auditable, HasFactory, HasTenant, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'cnpj',
        'address',
        'phone',
        'email',
        'contact_person',
    ];

    /** @return HasMany<Instrument, $this> */
    public function instruments(): HasMany
    {
        return $this->hasMany(Instrument::class);
    }
}
