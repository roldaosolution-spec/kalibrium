<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Extends Sanctum's PersonalAccessToken to bypass TenantScope when resolving the
 * tokenable owner. Authentication happens before any tenant context is established;
 * the standard morphTo relation would trigger TenantScope and throw RuntimeException.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * @return MorphTo<Model, $this>
     */
    #[\Override]
    public function tokenable(): MorphTo
    {
        return parent::tokenable()->withoutGlobalScope(TenantScope::class);
    }
}
