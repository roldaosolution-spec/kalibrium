<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Scopes\TenantScope;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * User provider that bypasses TenantScope for authentication queries.
 *
 * Authentication must happen before a tenant context exists — we identify
 * users globally (emails are unique across tenants) then let SetTenantContext
 * middleware inject isolation for all subsequent requests.
 */
class TenantAgnosticUserProvider extends EloquentUserProvider
{
    /**
     * @param  Model|null  $model
     * @return Builder<Model>
     */
    protected function newModelQuery($model = null): Builder
    {
        $instance = $model ?? $this->createModel();

        /** @var Builder<Model> */
        return $instance->newQuery()->withoutGlobalScope(TenantScope::class);
    }
}
