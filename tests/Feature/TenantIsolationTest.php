<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// ──────────────────────────────────────────────────────────────────────────────
// Slice 001 regression — TenantScope isolation
// ──────────────────────────────────────────────────────────────────────────────

// AC-001-03: user do Tenant A não acessa dados do Tenant B
it('[AC-001-03] TenantScope impede acesso a registros de outro tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    TenantContext::set($tenantA->id);
    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);

    TenantContext::set($tenantB->id);
    $usersVisibleFromB = User::all();

    expect($usersVisibleFromB->pluck('id'))->not->toContain($userA->id);

    TenantContext::clear();
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-002-01: tenant_id auto-injected from auth context on record creation
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-002-01: auto-injeção de tenant_id na criação', function () {
    afterEach(fn () => TenantContext::clear());

    it('[AC-002-01] HasTenant injeta tenant_id automaticamente a partir do contexto', function () {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->create();

        expect($user->tenant_id)->toBe($tenant->id);
    });

    it('[AC-002-01] HasTenant lança RuntimeException ao criar sem contexto definido', function () {
        TenantContext::clear();

        expect(fn () => User::factory()->create())->toThrow(RuntimeException::class);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-002-02: TenantScope filters all Eloquent queries by current tenant
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-002-02: TenantScope filtra queries Eloquent pelo tenant atual', function () {
    afterEach(fn () => TenantContext::clear());

    it('[AC-002-02] User::all() retorna apenas registros do tenant em contexto', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        $userA1 = User::factory()->create(['tenant_id' => $tenantA->id]);
        $userA2 = User::factory()->create(['tenant_id' => $tenantA->id]);

        TenantContext::set($tenantB->id);
        $userB1 = User::factory()->create(['tenant_id' => $tenantB->id]);

        TenantContext::set($tenantA->id);
        $visible = User::all();

        expect($visible)->toHaveCount(2)
            ->and($visible->pluck('id'))->toContain($userA1->id)
            ->and($visible->pluck('id'))->toContain($userA2->id)
            ->and($visible->pluck('id'))->not->toContain($userB1->id);
    });

    it('[AC-002-02] TenantScope lança RuntimeException quando contexto não está definido', function () {
        TenantContext::clear();

        expect(fn () => User::all())->toThrow(RuntimeException::class);
    });

    it('[AC-002-02] User::find não retorna registro pertencente a outro tenant', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantB->id);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        TenantContext::set($tenantA->id);
        $found = User::find($userB->id);

        expect($found)->toBeNull();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-002-03: PostgreSQL RLS policy blocks cross-tenant raw SQL (PostgreSQL only)
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-002-03: RLS bloqueia SQL bruto entre tenants', function () {
    afterEach(fn () => TenantContext::clear());

    it('[AC-002-03] Política RLS tenant_isolation existe e está configurada na tabela users', function () {
        $policies = DB::select("
            SELECT p.polname, pg_get_expr(p.polqual, p.polrelid) AS policy_using
            FROM pg_policy p
            JOIN pg_class c ON c.oid = p.polrelid
            WHERE c.relname = 'users' AND p.polname = 'tenant_isolation'
        ");

        expect($policies)->not->toBeEmpty()
            ->and($policies[0]->polname)->toBe('tenant_isolation')
            ->and($policies[0]->policy_using)->toContain('current_tenant_id');
    })->skip(fn () => config('database.default') !== 'pgsql', 'Requer PostgreSQL');

    it('[AC-002-03] FORCE ROW LEVEL SECURITY habilitado na tabela users', function () {
        $result = DB::select("
            SELECT relrowsecurity, relforcerowsecurity
            FROM pg_class WHERE relname = 'users'
        ");

        expect($result)->not->toBeEmpty()
            ->and((bool) $result[0]->relrowsecurity)->toBeTrue()
            ->and((bool) $result[0]->relforcerowsecurity)->toBeTrue();
    })->skip(fn () => config('database.default') !== 'pgsql', 'Requer PostgreSQL');

    it('[AC-002-03] RLS bloqueia SQL bruto com tenant errado ao operar como kalibrium_app', function () {
        $roleExists = DB::select("SELECT 1 AS r FROM pg_roles WHERE rolname = 'kalibrium_app'");

        if (empty($roleExists)) {
            test()->skip('Role kalibrium_app não encontrada — migration pode não ter sido aplicada.');
        }

        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        // Insert Tenant B user directly as superuser (bypasses Eloquent + RLS)
        $userBId = (string) Str::uuid();

        DB::table('users')->insert([
            'id' => $userBId,
            'tenant_id' => $tenantB->id,
            'name' => 'Usuário Tenant B (raw insert)',
            'email' => 'userb-rls@test.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::unprepared('GRANT SELECT ON users TO kalibrium_app');

        // Set Tenant A context then switch to non-superuser role so RLS takes effect
        DB::statement('SET LOCAL app.current_tenant_id = ?', [$tenantA->id]);
        DB::statement('SET LOCAL ROLE kalibrium_app');

        try {
            // Raw SQL: RLS must filter out Tenant B's row even without explicit WHERE on tenant_id
            $result = DB::select('SELECT id FROM users WHERE id = ?', [$userBId]);

            expect($result)->toBeEmpty('RLS deve impedir leitura de dados de outro tenant via SQL bruto');
        } finally {
            DB::statement('RESET ROLE');
        }
    })->skip(fn () => config('database.default') !== 'pgsql', 'Requer PostgreSQL');
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-002-04: Tenant A cannot see Tenant B data
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-002-04: Tenant A não acessa dados do Tenant B', function () {
    afterEach(fn () => TenantContext::clear());

    it('[AC-002-04] Tenant A não vê usuários do Tenant B via Eloquent', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantB->id);
        $usersB = User::factory()->count(3)->create(['tenant_id' => $tenantB->id]);

        TenantContext::set($tenantA->id);
        $usersA = User::factory()->count(2)->create(['tenant_id' => $tenantA->id]);

        $visibleFromA = User::all();

        expect($visibleFromA)->toHaveCount(2);

        foreach ($usersB as $userB) {
            expect($visibleFromA->pluck('id'))->not->toContain($userB->id);
        }

        foreach ($usersA as $userA) {
            expect($visibleFromA->pluck('id'))->toContain($userA->id);
        }
    });

    it('[AC-002-04] User::where não vaza registros de outro tenant', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantB->id);
        $userB = User::factory()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Usuário Exclusivo Tenant B',
        ]);

        TenantContext::set($tenantA->id);
        $found = User::where('name', $userB->name)->first();

        expect($found)->toBeNull();
    });

    it('[AC-002-04] factory()->forTenant() vincula usuário ao tenant correto', function () {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->forTenant($tenant)->create();

        expect($user->tenant_id)->toBe($tenant->id);

        $found = User::find($user->id);

        expect($found)->not->toBeNull();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// F4: IDOR — authenticated user cannot access cross-tenant records by direct ID
// ──────────────────────────────────────────────────────────────────────────────

describe('F4: IDOR — acesso cross-tenant por ID direto bloqueado', function () {
    afterEach(fn () => TenantContext::clear());

    it('[F4] usuário do Tenant A não obtém dados do Tenant B via /api/user por ID', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        // Create user in Tenant B (bypass Eloquent scope via direct factory)
        TenantContext::set($tenantB->id);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);
        TenantContext::clear();

        // Authenticate as Tenant A user
        TenantContext::set($tenantA->id);
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        TenantContext::clear();

        // User A tries to resolve Tenant B's user — TenantScope must block it
        TenantContext::set($tenantA->id);
        $found = User::find($userB->id);
        TenantContext::clear();

        expect($found)->toBeNull('IDOR: User A não deve acessar User B via User::find($id)');
    });

    it('[F4] User::where(id) não vaza registro IDOR de outro tenant', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantB->id);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        TenantContext::set($tenantA->id);
        $found = User::where('id', $userB->id)->first();
        TenantContext::clear();

        expect($found)->toBeNull('IDOR: User::where(id) não deve retornar registro de outro tenant');
    });

    it('[F4] HTTP GET /api/user retorna dados do usuário autenticado, não de outro tenant', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantB->id);
        $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

        TenantContext::set($tenantA->id);
        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        TenantContext::clear();

        $response = $this->actingAs($userA, 'sanctum')->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('id', $userA->id)
            ->assertJsonMissing(['id' => $userB->id]);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-002-05: RLS policy active in CI migration (PostgreSQL only)
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-002-05: RLS ativo após migration em CI', function () {
    it('[AC-002-05] RLS habilitado na tabela users após execução das migrations', function () {
        $result = DB::select("SELECT relrowsecurity FROM pg_class WHERE relname = 'users'");

        expect($result)->not->toBeEmpty()
            ->and((bool) $result[0]->relrowsecurity)->toBeTrue();
    })->skip(fn () => config('database.default') !== 'pgsql', 'Requer PostgreSQL');

    it('[AC-002-05] Política tenant_isolation existe na tabela audits após migration', function () {
        $policies = DB::select("
            SELECT p.polname
            FROM pg_policy p
            JOIN pg_class c ON c.oid = p.polrelid
            WHERE c.relname = 'audits' AND p.polname = 'tenant_isolation'
        ");

        expect($policies)->not->toBeEmpty();
    })->skip(fn () => config('database.default') !== 'pgsql', 'Requer PostgreSQL');

    it('[AC-002-05] Role kalibrium_app existe sem superuser e sem BYPASSRLS', function () {
        $role = DB::select("
            SELECT rolsuper, rolbypassrls
            FROM pg_roles WHERE rolname = 'kalibrium_app'
        ");

        expect($role)->not->toBeEmpty()
            ->and((bool) $role[0]->rolsuper)->toBeFalse()
            ->and((bool) $role[0]->rolbypassrls)->toBeFalse();
    })->skip(fn () => config('database.default') !== 'pgsql', 'Requer PostgreSQL');
});
