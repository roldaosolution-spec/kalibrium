<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use RuntimeException;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = TenantContext::getId();

        if ($tenantId === null) {
            throw new RuntimeException(
                'TenantScope aplicado sem tenant_id no contexto. '.
                'Certifique-se de que SetTenantContext middleware foi executado.'
            );
        }

        $builder->where($model->getTable().'.tenant_id', $tenantId);
    }
}
