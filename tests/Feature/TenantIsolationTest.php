<?php

use App\Models\Scopes\TenantContext;
use App\Models\Tenant;
use App\Models\User;

// AC-001-03: isolamento multi-tenant — user do Tenant A não acessa dados do Tenant B
it('[AC-001-03] TenantScope impede acesso a registros de outro tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    TenantContext::set($tenantA->id);
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    TenantContext::set($tenantB->id);
    $usersVisibleFromB = User::all();

    expect($usersVisibleFromB->pluck('id'))->not->toContain($userA->id);
})->skip('Requer integração com PostgreSQL — executar no CI');
