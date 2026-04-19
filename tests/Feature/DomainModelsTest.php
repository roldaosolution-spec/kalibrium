<?php

declare(strict_types=1);

use App\Enums\Domain;
use App\Enums\Role;
use App\Models\Client;
use App\Models\Instrument;
use App\Models\Procedure;
use App\Models\Standard;
use App\Models\TechnicianCompetency;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\ClientPolicy;
use App\Policies\InstrumentPolicy;
use App\Policies\ProcedurePolicy;
use App\Policies\StandardPolicy;
use App\Policies\TechnicianCompetencyPolicy;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

afterEach(fn () => TenantContext::clear());

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-01 / AC-004-02 : Migrations + Models
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-01/02: migrations e modelos', function (): void {
    it('[AC-004-02] Client salva e recupera todos os campos', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $client = Client::factory()->forTenant($tenant)->create([
            'name' => 'Empresa Teste Ltda',
            'cnpj' => '12.345.678/0001-90',
            'address' => 'Rua das Flores, 123',
            'phone' => '(11) 99999-9999',
            'email' => 'contato@empresa.com.br',
            'contact_person' => 'João Silva',
        ]);

        $found = Client::find($client->id);

        expect($found)->not->toBeNull()
            ->and($found->name)->toBe('Empresa Teste Ltda')
            ->and($found->cnpj)->toBe('12.345.678/0001-90')
            ->and($found->contact_person)->toBe('João Silva')
            ->and($found->tenant_id)->toBe($tenant->id);
    });

    it('[AC-004-02] Instrument salva e recupera todos os campos com enums corretos', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $instrument = Instrument::factory()->forTenant($tenant)->create([
            'serial_number' => 'SN-TEST-001',
            'type' => 'Paquímetro',
            'domain' => Domain::Dimensional->value,
            'range_min' => '0.00',
            'range_max' => '150.00',
            'resolution' => '0.02',
        ]);

        $found = Instrument::find($instrument->id);

        expect($found)->not->toBeNull()
            ->and($found->serial_number)->toBe('SN-TEST-001')
            ->and($found->domain)->toBe(Domain::Dimensional)
            ->and($found->domain->label())->toBe('Dimensional');
    });

    it('[AC-004-02] Standard salva e recupera domain + datas como tipos corretos', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $standard = Standard::factory()->forTenant($tenant)->create([
            'domain' => Domain::Massa->value,
            'certificate_date' => '2025-01-15',
            'validity_date' => '2027-01-15',
        ]);

        $found = Standard::find($standard->id);

        expect($found->domain)->toBe(Domain::Massa)
            ->and($found->certificate_date)->toBeInstanceOf(Carbon::class)
            ->and($found->validity_date->format('Y-m-d'))->toBe('2027-01-15');
    });

    it('[AC-004-02] Procedure steps é cast como array', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $steps = [['order' => 1, 'description' => 'Passo 1']];
        $procedure = Procedure::factory()->forTenant($tenant)->create(['steps' => $steps]);

        $found = Procedure::find($procedure->id);

        expect($found->steps)->toBeArray()
            ->and($found->steps[0]['description'])->toBe('Passo 1');
    });

    it('[AC-004-02] TechnicianCompetency vincula corretamente ao User', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $competency = TechnicianCompetency::factory()
            ->forTenant($tenant)
            ->forUser($user)
            ->forDomain(Domain::Temperatura)
            ->create();

        $found = TechnicianCompetency::find($competency->id);

        expect($found->user_id)->toBe($user->id)
            ->and($found->domain)->toBe(Domain::Temperatura)
            ->and($found->user->id)->toBe($user->id);
    });

    it('[AC-004-02] Instrument relaciona-se com Client', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $client = Client::factory()->forTenant($tenant)->create();
        $instrument = Instrument::factory()->forTenant($tenant)->forClient($client)->create();

        expect($instrument->client->id)->toBe($client->id)
            ->and($client->instruments()->count())->toBe(1);
    });

    it('[AC-004-02] HasTenant injeta tenant_id automaticamente em Client', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $client = Client::factory()->create(['name' => 'Auto Tenant Test']);

        expect($client->tenant_id)->toBe($tenant->id);
    });

    it('[AC-004-02] HasTenant lança RuntimeException sem contexto ao criar Instrument', function (): void {
        TenantContext::clear();
        expect(fn () => Instrument::factory()->make()->save())->toThrow(RuntimeException::class);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-03 : Tenant isolation (Eloquent scope)
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-03: isolamento de tenant nas queries', function (): void {
    it('[AC-004-03] Client::all() retorna apenas clientes do tenant em contexto', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        Client::factory()->forTenant($tenantA)->count(2)->create();

        TenantContext::set($tenantB->id);
        Client::factory()->forTenant($tenantB)->count(3)->create();

        TenantContext::set($tenantA->id);
        expect(Client::count())->toBe(2);
    });

    it('[AC-004-03] Instrument::find não retorna instrumento de outro tenant', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantB->id);
        $instrumentB = Instrument::factory()->forTenant($tenantB)->create();

        TenantContext::set($tenantA->id);
        expect(Instrument::find($instrumentB->id))->toBeNull();
    });

    it('[AC-004-03] Standard::all() isola corretamente entre tenants', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        Standard::factory()->forTenant($tenantA)->count(2)->create();

        TenantContext::set($tenantB->id);
        Standard::factory()->forTenant($tenantB)->count(1)->create();

        TenantContext::set($tenantA->id);
        expect(Standard::count())->toBe(2);
    });

    it('[AC-004-03] Procedure::all() isola corretamente entre tenants', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        Procedure::factory()->forTenant($tenantA)->count(3)->create();

        TenantContext::set($tenantB->id);
        Procedure::factory()->forTenant($tenantB)->count(1)->create();

        TenantContext::set($tenantA->id);
        expect(Procedure::count())->toBe(3);
    });

    it('[AC-004-03] TechnicianCompetency isola corretamente entre tenants', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        $userA = User::factory()->forTenant($tenantA)->create();
        TechnicianCompetency::factory()->forTenant($tenantA)->forUser($userA)->create();

        TenantContext::set($tenantB->id);
        $userB = User::factory()->forTenant($tenantB)->create();
        TechnicianCompetency::factory()->forTenant($tenantB)->forUser($userB)->create();

        TenantContext::set($tenantA->id);
        expect(TechnicianCompetency::count())->toBe(1);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-03 : RLS policies exist (PostgreSQL only)
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-03: RLS policies nas tabelas de domínio', function (): void {
    $tables = ['clients', 'instruments', 'standards', 'procedures', 'technician_competencies'];

    foreach ($tables as $table) {
        it("[AC-004-03] Política tenant_isolation existe na tabela {$table}", function () use ($table): void {
            $policies = DB::select("
                SELECT p.polname
                FROM pg_policy p
                JOIN pg_class c ON c.oid = p.polrelid
                WHERE c.relname = ? AND p.polname = 'tenant_isolation'
            ", [$table]);

            expect($policies)->not->toBeEmpty();
        })->skip(fn (): bool => config('database.default') !== 'pgsql', 'Requer PostgreSQL');

        it("[AC-004-03] FORCE ROW LEVEL SECURITY habilitado na tabela {$table}", function () use ($table): void {
            $result = DB::select(
                'SELECT relrowsecurity, relforcerowsecurity FROM pg_class WHERE relname = ?',
                [$table],
            );

            expect($result)->not->toBeEmpty()
                ->and((bool) $result[0]->relrowsecurity)->toBeTrue()
                ->and((bool) $result[0]->relforcerowsecurity)->toBeTrue();
        })->skip(fn (): bool => config('database.default') !== 'pgsql', 'Requer PostgreSQL');
    }
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-05 : Authorization Policies
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-05: políticas de autorização', function (): void {
    it('[AC-004-05] Gerente pode criar/editar/excluir Client', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $gerente = User::factory()->forTenant($tenant)->withRole(Role::Gerente)->create();
        $client = Client::factory()->forTenant($tenant)->create();

        $policy = new ClientPolicy;

        expect($policy->viewAny($gerente))->toBeTrue()
            ->and($policy->create($gerente))->toBeTrue()
            ->and($policy->update($gerente, $client))->toBeTrue()
            ->and($policy->delete($gerente, $client))->toBeTrue();
    });

    it('[AC-004-05] Administrativo pode listar/criar/editar mas não excluir Client', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $admin = User::factory()->forTenant($tenant)->withRole(Role::Administrativo)->create();
        $client = Client::factory()->forTenant($tenant)->create();

        $policy = new ClientPolicy;

        expect($policy->viewAny($admin))->toBeTrue()
            ->and($policy->create($admin))->toBeTrue()
            ->and($policy->update($admin, $client))->toBeTrue()
            ->and($policy->delete($admin, $client))->toBeFalse();
    });

    it('[AC-004-05] Tecnico não pode listar Clients (viewAny restrito a gestão)', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $tecnico = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();

        $policy = new ClientPolicy;

        expect($policy->viewAny($tecnico))->toBeFalse();
    });

    it('[AC-004-05] Tecnico pode ver mas não criar/editar/excluir Instruments', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $tecnico = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $instrument = Instrument::factory()->forTenant($tenant)->create();

        $policy = new InstrumentPolicy;

        expect($policy->viewAny($tecnico))->toBeTrue()
            ->and($policy->view($tecnico, $instrument))->toBeTrue()
            ->and($policy->create($tecnico))->toBeFalse()
            ->and($policy->update($tecnico, $instrument))->toBeFalse()
            ->and($policy->delete($tecnico, $instrument))->toBeFalse();
    });

    it('[AC-004-05] Apenas Gerente pode criar/editar Procedures; Tecnico pode listar', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $gerente = User::factory()->forTenant($tenant)->withRole(Role::Gerente)->create();
        $admin = User::factory()->forTenant($tenant)->withRole(Role::Administrativo)->create();
        $tecnico = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $procedure = Procedure::factory()->forTenant($tenant)->create();

        $policy = new ProcedurePolicy;

        expect($policy->viewAny($gerente))->toBeTrue()
            ->and($policy->viewAny($admin))->toBeTrue()
            ->and($policy->viewAny($tecnico))->toBeTrue()
            ->and($policy->create($gerente))->toBeTrue()
            ->and($policy->update($gerente, $procedure))->toBeTrue()
            ->and($policy->create($admin))->toBeFalse()
            ->and($policy->update($admin, $procedure))->toBeFalse();
    });

    it('[AC-004-05] TechnicianCompetency: Tecnico pode ver sua própria competência', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $gerente = User::factory()->forTenant($tenant)->withRole(Role::Gerente)->create();
        $tecnico = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $otherTecnico = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $competency = TechnicianCompetency::factory()->forTenant($tenant)->forUser($tecnico)->create();

        $policy = new TechnicianCompetencyPolicy;

        expect($policy->view($tecnico, $competency))->toBeTrue()
            ->and($policy->view($otherTecnico, $competency))->toBeFalse()
            ->and($policy->view($gerente, $competency))->toBeTrue()
            ->and($policy->viewAny($tecnico))->toBeFalse()
            ->and($policy->viewAny($gerente))->toBeTrue();
    });

    it('[AC-004-05] Standard policy: Gerente gerencia, Tecnico pode listar/ver mas não modificar', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $gerente = User::factory()->forTenant($tenant)->withRole(Role::Gerente)->create();
        $tecnico = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $standard = Standard::factory()->forTenant($tenant)->create();

        $policy = new StandardPolicy;

        expect($policy->viewAny($gerente))->toBeTrue()
            ->and($policy->viewAny($tecnico))->toBeTrue()
            ->and($policy->create($gerente))->toBeTrue()
            ->and($policy->delete($gerente, $standard))->toBeTrue()
            ->and($policy->create($tecnico))->toBeFalse()
            ->and($policy->delete($tecnico, $standard))->toBeFalse()
            ->and($policy->view($tecnico, $standard))->toBeTrue();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-07 : Standard validity check
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-07: verificação de validade do Padrão', function (): void {
    it('[AC-004-07] Standard não expirado isValidForUse() retorna true', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $standard = Standard::factory()->forTenant($tenant)->create([
            'validity_date' => now()->addYear()->format('Y-m-d'),
        ]);

        expect($standard->isExpired())->toBeFalse()
            ->and($standard->isValidForUse())->toBeTrue();
    });

    it('[AC-004-07] Standard expirado isExpired() retorna true e isValidForUse() retorna false', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $standard = Standard::factory()->expired()->forTenant($tenant)->create();

        expect($standard->isExpired())->toBeTrue()
            ->and($standard->isValidForUse())->toBeFalse();
    });

    it('[AC-004-07] Standard com validade exatamente hoje está expirado (isPast strict)', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $standard = Standard::factory()->forTenant($tenant)->create([
            'validity_date' => now()->subDay()->format('Y-m-d'),
        ]);

        expect($standard->isExpired())->toBeTrue();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-08 : TechnicianCompetency expiry check
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-08: verificação de competência do técnico', function (): void {
    it('[AC-004-08] TechnicianCompetency válida retorna isExpired() false', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $competency = TechnicianCompetency::factory()
            ->forTenant($tenant)
            ->forUser($user)
            ->forDomain(Domain::Dimensional)
            ->create(['expires_at' => now()->addYear()->format('Y-m-d')]);

        expect($competency->isExpired())->toBeFalse()
            ->and($competency->isValidForDomain(Domain::Dimensional))->toBeTrue();
    });

    it('[AC-004-08] TechnicianCompetency expirada bloqueia domínio', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $competency = TechnicianCompetency::factory()
            ->expired()
            ->forTenant($tenant)
            ->forUser($user)
            ->forDomain(Domain::Pressao)
            ->create();

        expect($competency->isExpired())->toBeTrue()
            ->and($competency->isValidForDomain(Domain::Pressao))->toBeFalse();
    });

    it('[AC-004-08] TechnicianCompetency válida para domínio A não é válida para domínio B', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $competency = TechnicianCompetency::factory()
            ->forTenant($tenant)
            ->forUser($user)
            ->forDomain(Domain::Temperatura)
            ->create(['expires_at' => now()->addYear()->format('Y-m-d')]);

        expect($competency->isValidForDomain(Domain::Temperatura))->toBeTrue()
            ->and($competency->isValidForDomain(Domain::Massa))->toBeFalse();
    });

    it('[AC-004-08] TechnicianCompetency sem expires_at nunca expira', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->forTenant($tenant)->withRole(Role::Tecnico)->create();
        $competency = TechnicianCompetency::factory()
            ->forTenant($tenant)
            ->forUser($user)
            ->create(['expires_at' => null]);

        expect($competency->isExpired())->toBeFalse();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-04 : Factories
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-04: factories', function (): void {
    it('[AC-004-04] ClientFactory cria cliente com CNPJ válido no formato correto', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $client = Client::factory()->forTenant($tenant)->create();

        expect($client->cnpj)->toMatch('/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/')
            ->and($client->name)->not->toBeEmpty();
    });

    it('[AC-004-04] StandardFactory expired() cria standard com validity_date no passado', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $standard = Standard::factory()->expired()->forTenant($tenant)->create();

        expect($standard->validity_date->isPast())->toBeTrue();
    });

    it('[AC-004-04] TechnicianCompetencyFactory expired() cria competência vencida', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $user = User::factory()->forTenant($tenant)->create();
        $competency = TechnicianCompetency::factory()->expired()->forTenant($tenant)->forUser($user)->create();

        expect($competency->expires_at->isPast())->toBeTrue();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-01 : Soft deletes
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-01: soft deletes', function (): void {
    it('[AC-004-01] Client soft-deleted não aparece em queries normais', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $client = Client::factory()->forTenant($tenant)->create();
        $id = $client->id;
        $client->delete();

        expect(Client::find($id))->toBeNull()
            ->and(Client::withTrashed()->find($id))->not->toBeNull();
    });

    it('[AC-004-01] Instrument soft-deleted não aparece em queries normais', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $instrument = Instrument::factory()->forTenant($tenant)->create();
        $id = $instrument->id;
        $instrument->delete();

        expect(Instrument::find($id))->toBeNull();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// Domain Enum
// ──────────────────────────────────────────────────────────────────────────────

describe('Domain enum', function (): void {
    it('Domain enum tem 4 casos com labels em PT-BR', function (): void {
        expect(Domain::Dimensional->label())->toBe('Dimensional')
            ->and(Domain::Pressao->label())->toBe('Pressão')
            ->and(Domain::Massa->label())->toBe('Massa')
            ->and(Domain::Temperatura->label())->toBe('Temperatura');
    });

    it('Domain::values() retorna todos os valores string', function (): void {
        expect(Domain::values())->toHaveCount(4)
            ->and(Domain::values())->toContain('dimensional')
            ->and(Domain::values())->toContain('pressao')
            ->and(Domain::values())->toContain('massa')
            ->and(Domain::values())->toContain('temperatura');
    });
});
