<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Adds tenant isolation to an Eloquent model: auto-injects tenant_id on create and applies TenantScope globally. */
trait HasTenant
{
    public static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (self $model) {
            if (empty($model->tenant_id)) {
                $tenantId = TenantContext::getId();

                if ($tenantId === null) {
                    throw new \RuntimeException(
                        'Não é possível criar ' . class_basename($model) . ' sem tenant_id no contexto.',
                    );
                }

                $model->tenant_id = $tenantId;
            }
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
