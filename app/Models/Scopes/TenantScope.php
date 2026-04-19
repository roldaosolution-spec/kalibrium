<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use RuntimeException;

/** @implements Scope<Model> */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = TenantContext::getId();

        if ($tenantId === null) {
            throw new RuntimeException(
                'TenantScope aplicado sem tenant_id no contexto. ' .
                'Certifique-se de que SetTenantContext middleware foi executado.',
            );
        }

        $builder->where($model->getTable() . '.tenant_id', $tenantId);
    }
}
