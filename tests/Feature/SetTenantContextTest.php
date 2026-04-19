<?php

use App\Models\Scopes\TenantContext;
use App\Models\Tenant;
use App\Models\User;

// ──────────────────────────────────────────────────────────────────────────────
// SetTenantContext middleware coverage
// ──────────────────────────────────────────────────────────────────────────────

describe('SetTenantContext middleware', function () {
    afterEach(fn () => TenantContext::clear());

    it('[AC-001-02] requisição sem autenticação retorna 401', function () {
        $this->getJson('/api/user')
            ->assertStatus(401);
    });

    it('[AC-001-02] usuário autenticado com tenant_id válido define contexto e prossegue', function () {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user')
            ->assertStatus(200);

        expect(TenantContext::getId())->toBe($tenant->id);
    });

    it('[AC-001-02] HasTenant::tenant() retorna o Tenant correto', function () {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $related = $user->tenant;

        expect($related)->not->toBeNull()
            ->and($related->id)->toBe($tenant->id);
    });
});
