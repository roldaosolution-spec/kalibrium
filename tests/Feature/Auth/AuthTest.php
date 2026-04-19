<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use PragmaRX\Google2FA\Google2FA;

// ─────────────────────────────────────────────────────────────────────────────
// AC-003-01: User registration creates user scoped to tenant
// ─────────────────────────────────────────────────────────────────────────────

describe('AC-003-01: registro cria usuário com tenant correto', function (): void {
    afterEach(fn () => TenantContext::clear());

    it('[AC-003-01] POST /register cria usuário vinculado ao tenant', function (): void {
        $tenant = Tenant::factory()->create();

        $response = $this->post('/register', [
            'name' => 'João Silva',
            'email' => 'joao@calibracao.test',
            'password' => 'Senha@123!',
            'password_confirmation' => 'Senha@123!',
            'tenant_id' => $tenant->id,
            'role' => Role::Tecnico->value,
        ]);

        $response->assertRedirect('/home');

        TenantContext::set($tenant->id);
        $user = User::where('email', 'joao@calibracao.test')->first();
        TenantContext::clear();

        expect($user)->not->toBeNull()
            ->and($user->tenant_id)->toBe($tenant->id)
            ->and($user->role->value)->toBe(Role::Tecnico->value);
    });

    it('[AC-003-01] registro sem tenant_id retorna erro de validação', function (): void {
        $response = $this->post('/register', [
            'name' => 'Ana Costa',
            'email' => 'ana@calibracao.test',
            'password' => 'Senha@123!',
            'password_confirmation' => 'Senha@123!',
        ]);

        $response->assertSessionHasErrors('tenant_id');
    });

    it('[AC-003-01] registro com role inválido retorna erro de validação', function (): void {
        $tenant = Tenant::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Pedro Lima',
            'email' => 'pedro@calibracao.test',
            'password' => 'Senha@123!',
            'password_confirmation' => 'Senha@123!',
            'tenant_id' => $tenant->id,
            'role' => 'invalido',
        ]);

        $response->assertSessionHasErrors('role');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// AC-003-02: Login returns Sanctum token
// ─────────────────────────────────────────────────────────────────────────────

describe('AC-003-02: login retorna token Sanctum', function (): void {
    afterEach(fn () => TenantContext::clear());

    it('[AC-003-02] POST /api/tokens/create retorna token para credenciais válidas', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->create([
            'email' => 'api@calibracao.test',
            'password' => Hash::make('Senha@123!'),
            'tenant_id' => $tenant->id,
        ]);
        TenantContext::clear();

        $response = $this->postJson('/api/tokens/create', [
            'email' => 'api@calibracao.test',
            'password' => 'Senha@123!',
            'device_name' => 'mobile-test',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token']);

        expect($response->json('token'))->toBeString()->not->toBeEmpty();
    });

    it('[AC-003-02] token inválido retorna 422', function (): void {
        $response = $this->postJson('/api/tokens/create', [
            'email' => 'naoexiste@calibracao.test',
            'password' => 'senhaerrada',
        ]);

        $response->assertUnprocessable();
    });

    it('[AC-003-02] token pode ser usado para autenticar em /api/user', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        $tokenResponse = $this->postJson('/api/tokens/create', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $tokenResponse->json('token');

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('id', $user->id);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// AC-003-03: Role middleware blocks unauthorized access
// ─────────────────────────────────────────────────────────────────────────────

describe('AC-003-03: middleware EnsureRole bloqueia acesso por perfil', function (): void {
    afterEach(fn () => TenantContext::clear());

    it('[AC-003-03] gerente pode acessar rota exclusiva do gerente', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $gerente = User::factory()->withRole(Role::Gerente)->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        Route::get('/test-gerente', fn () => response('ok'))
            ->middleware(['auth:sanctum', 'role:gerente']);

        $response = $this->actingAs($gerente, 'sanctum')->getJson('/test-gerente');

        $response->assertOk();
    });

    it('[AC-003-03] tecnico recebe 403 ao acessar rota exclusiva do gerente', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $tecnico = User::factory()->withRole(Role::Tecnico)->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        Route::get('/test-gerente-deny', fn () => response('ok'))
            ->middleware(['auth:sanctum', 'role:gerente']);

        $response = $this->actingAs($tecnico, 'sanctum')->getJson('/test-gerente-deny');

        $response->assertForbidden();
    });

    it('[AC-003-03] middleware aceita múltiplos roles (OR)', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $vendedor = User::factory()->withRole(Role::Vendedor)->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        Route::get('/test-multi-role', fn () => response('ok'))
            ->middleware(['auth:sanctum', 'role:gerente,vendedor']);

        $response = $this->actingAs($vendedor, 'sanctum')->getJson('/test-multi-role');

        $response->assertOk();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// AC-003-04: 2FA TOTP can be enabled and verified
// ─────────────────────────────────────────────────────────────────────────────

describe('AC-003-04: 2FA TOTP pode ser habilitado e verificado', function (): void {
    afterEach(fn () => TenantContext::clear());

    it('[AC-003-04] habilitar 2FA cria segredo TOTP no usuário', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication')
            ->assertStatus(200);

        // Reload from DB bypassing TenantScope to inspect the secret
        $fresh = User::withoutGlobalScope(TenantScope::class)->find($user->id);

        expect($fresh->two_factor_secret)->not->toBeNull();
    });

    it('[AC-003-04] URL de QR Code é retornada após habilitar 2FA', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson('/user/two-factor-qr-code');

        $response->assertOk()
            ->assertJsonStructure(['svg']);
    });

    it('[AC-003-04] confirmação com código TOTP válido ativa 2FA', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        TenantContext::clear();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');

        $fresh = User::withoutGlobalScope(TenantScope::class)->find($user->id);
        $rawSecret = decrypt($fresh->two_factor_secret);

        $google2fa = new Google2FA;
        $code = $google2fa->getCurrentOtp($rawSecret);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/confirmed-two-factor-authentication', ['code' => $code])
            ->assertStatus(200);

        $confirmed = User::withoutGlobalScope(TenantScope::class)->find($user->id);
        expect($confirmed->two_factor_confirmed_at)->not->toBeNull();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// AC-003-05: User from tenant A cannot authenticate as tenant B
// ─────────────────────────────────────────────────────────────────────────────

describe('AC-003-05: usuário do Tenant A não autentica como Tenant B', function (): void {
    afterEach(fn () => TenantContext::clear());

    it('[AC-003-05] token de Tenant A não acessa dados de Tenant B', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        TenantContext::clear();

        TenantContext::set($tenantB->id);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);
        TenantContext::clear();

        // Authenticate as User A
        $tokenResponse = $this->postJson('/api/tokens/create', [
            'email' => $userA->email,
            'password' => 'password',
        ]);

        $tokenA = $tokenResponse->json('token');

        // /api/user returns User A's own data, not User B's
        $response = $this->withToken($tokenA)->getJson('/api/user');
        $response->assertOk()
            ->assertJsonPath('id', $userA->id)
            ->assertJsonMissing(['id' => $userB->id]);
    });

    it('[AC-003-05] credenciais do Tenant A não permitem login com e-mail do Tenant B', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        User::factory()->create([
            'email' => 'usera@calibracao.test',
            'password' => Hash::make('SenhaA@123!'),
            'tenant_id' => $tenantA->id,
        ]);
        TenantContext::clear();

        TenantContext::set($tenantB->id);
        User::factory()->create([
            'email' => 'userb@calibracao.test',
            'password' => Hash::make('SenhaB@456!'),
            'tenant_id' => $tenantB->id,
        ]);
        TenantContext::clear();

        // Wrong password for Tenant B's email
        $response = $this->postJson('/api/tokens/create', [
            'email' => 'userb@calibracao.test',
            'password' => 'SenhaA@123!',
        ]);

        $response->assertUnprocessable();
    });

    it('[AC-003-05] SetTenantContext injeta tenant_id correto do usuário autenticado', function (): void {
        $tenantA = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        TenantContext::clear();

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/user');
        $response->assertOk()
            ->assertJsonPath('tenant_id', $tenantA->id);
    });
});
