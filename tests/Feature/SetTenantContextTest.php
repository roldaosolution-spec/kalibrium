<?php

use App\Http\Middleware\SetTenantContext;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

// ──────────────────────────────────────────────────────────────────────────────
// SetTenantContext middleware coverage
// ──────────────────────────────────────────────────────────────────────────────

describe('SetTenantContext middleware', function (): void {
    afterEach(function (): void {
        TenantContext::clear();

        // SET (session-level) is not rolled back by RefreshDatabase's transaction ROLLBACK.
        // Explicitly reset to prevent GUC leakage between tests.
        if (config('database.default') === 'pgsql') {
            DB::statement('RESET app.current_tenant_id');
        }
    });

    it('[AC-001-02] requisição sem autenticação retorna 401', function (): void {
        $this->getJson('/api/user')
            ->assertStatus(401);
    });

    it('[AC-001-02] middleware aborta 401 quando usuário autenticado não tem tenant_id', function (): void {
        // Simulate an authenticated user whose tenant_id was somehow cleared.
        // Test the middleware directly to cover the abort(401) branch.
        $middleware = new SetTenantContext;
        $request = Request::create('/api/user', 'GET');

        $userWithoutTenant = User::factory()->make(['tenant_id' => null]);
        $request->setUserResolver(fn () => $userWithoutTenant);

        expect(fn (): Response => $middleware->handle($request, fn (): ResponseFactory|\Illuminate\Http\Response => response('ok')))
            ->toThrow(HttpException::class);
    });

    it('[AC-001-02] usuário autenticado com tenant_id válido define contexto e prossegue', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        // The middleware sets context during the request, then clears it in the finally
        // block to prevent connection-pool GUC leakage. A 200 response proves the
        // middleware ran successfully and TenantContext was properly set during the request.
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user')
            ->assertStatus(200)
            ->assertJsonPath('tenant_id', $tenant->id);
    });

    it('[AC-001-02] HasTenant::tenant() retorna o Tenant correto', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $related = $user->tenant;

        expect($related)->not->toBeNull()
            ->and($related->id)->toBe($tenant->id);
    });
});
