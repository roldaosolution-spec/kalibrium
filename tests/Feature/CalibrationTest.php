<?php

declare(strict_types=1);

use App\Enums\CalibrationStatus;
use App\Enums\Domain;
use App\Enums\Role;
use App\Enums\ServiceOrderMode;
use App\Enums\ServiceOrderStatus;
use App\Models\Calibration;
use App\Models\CalibrationPoint;
use App\Models\Client;
use App\Models\Instrument;
use App\Models\Procedure;
use App\Models\ServiceOrder;
use App\Models\Standard;
use App\Models\TechnicianCompetency;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\CalibrationPolicy;
use App\Policies\ServiceOrderPolicy;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

afterEach(fn () => TenantContext::clear());

// ──────────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────────

function makeCalibrationFixture(): array
{
    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant->id);

    $domain = Domain::Dimensional;
    $executor = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);
    $verifier = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);

    // Grant competencies
    TechnicianCompetency::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $executor->id,
        'domain' => $domain->value,
        'qualified_at' => Carbon::now()->subYear(),
        'expires_at' => Carbon::now()->addYear(),
    ]);
    TechnicianCompetency::factory()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $verifier->id,
        'domain' => $domain->value,
        'qualified_at' => Carbon::now()->subYear(),
        'expires_at' => Carbon::now()->addYear(),
    ]);

    $client = Client::factory()->forTenant($tenant)->create();
    $instrument = Instrument::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id, 'domain' => $domain->value]);
    $standard = Standard::factory()->create(['tenant_id' => $tenant->id, 'domain' => $domain->value]);
    $procedure = Procedure::factory()->create(['tenant_id' => $tenant->id, 'domain' => $domain->value]);
    $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id, 'status' => ServiceOrderStatus::InProgress->value]);
    $calibration = Calibration::factory()->create([
        'tenant_id' => $tenant->id,
        'service_order_id' => $os->id,
        'instrument_id' => $instrument->id,
        'standard_id' => $standard->id,
        'procedure_id' => $procedure->id,
    ]);

    return ['tenant' => $tenant, 'domain' => $domain, 'executor' => $executor, 'verifier' => $verifier, 'instrument' => $instrument, 'standard' => $standard, 'os' => $os, 'calibration' => $calibration];
}

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-01: Migrations
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-01: migrações', function (): void {
    it('[AC-005-01] tabela service_orders existe com colunas obrigatórias', function (): void {
        $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'service_orders'");
        $cols = array_column($columns, 'column_name');

        expect($cols)->toContain('id')
            ->toContain('tenant_id')
            ->toContain('number')
            ->toContain('mode')
            ->toContain('status')
            ->toContain('sla_date')
            ->toContain('assigned_technician_id')
            ->toContain('deleted_at');
    });

    it('[AC-005-01] tabela calibrations existe com colunas obrigatórias', function (): void {
        $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'calibrations'");
        $cols = array_column($columns, 'column_name');

        expect($cols)->toContain('id')
            ->toContain('tenant_id')
            ->toContain('service_order_id')
            ->toContain('instrument_id')
            ->toContain('executor_id')
            ->toContain('verifier_id')
            ->toContain('status')
            ->toContain('certificate_number');
    });

    it('[AC-005-01] tabela calibration_points existe com colunas obrigatórias', function (): void {
        $columns = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'calibration_points'");
        $cols = array_column($columns, 'column_name');

        expect($cols)->toContain('id')
            ->toContain('calibration_id')
            ->toContain('nominal_value')
            ->toContain('measured_value')
            ->toContain('deviation')
            ->toContain('uncertainty')
            ->toContain('pass');
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-02: Enums e modelos
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-02: enums e modelos', function (): void {
    it('[AC-005-02] ServiceOrderStatus tem 7 casos com labels PT-BR', function (): void {
        expect(ServiceOrderStatus::cases())->toHaveCount(7)
            ->and(ServiceOrderStatus::Draft->label())->toBe('Rascunho')
            ->and(ServiceOrderStatus::Closed->label())->toBe('Encerrada');
    });

    it('[AC-005-02] ServiceOrderMode tem 3 casos com labels PT-BR', function (): void {
        expect(ServiceOrderMode::cases())->toHaveCount(3)
            ->and(ServiceOrderMode::Bench->label())->toBe('Bancada')
            ->and(ServiceOrderMode::Field->label())->toBe('Campo')
            ->and(ServiceOrderMode::Umc->label())->toBe('UMC');
    });

    it('[AC-005-02] CalibrationStatus tem 6 casos com labels PT-BR', function (): void {
        expect(CalibrationStatus::cases())->toHaveCount(6)
            ->and(CalibrationStatus::Draft->label())->toBe('Rascunho')
            ->and(CalibrationStatus::Issued->label())->toBe('Certificado Emitido');
    });

    it('[AC-005-02] ServiceOrder cast enums corretamente', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id]);

        expect($os->status)->toBeInstanceOf(ServiceOrderStatus::class)
            ->and($os->mode)->toBeInstanceOf(ServiceOrderMode::class);
    });

    it('[AC-005-02] ServiceOrder tem tenant_id auto-injetado', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id]);

        expect($os->tenant_id)->toBe($tenant->id);
    });

    it('[AC-005-02] Calibration cast status enum corretamente', function (): void {
        $fixture = makeCalibrationFixture();

        expect($fixture['calibration']->status)->toBeInstanceOf(CalibrationStatus::class)
            ->and($fixture['calibration']->status)->toBe(CalibrationStatus::Draft);
    });

    it('[AC-005-02] CalibrationPoint salva e recupera todos os campos', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $point = CalibrationPoint::factory()->forCalibration($fixture['calibration'])->create([
            'nominal_value' => 100.5,
            'measured_value' => 100.502,
            'unit' => 'mm',
            'deviation' => 0.002,
            'uncertainty' => 0.01,
            'pass' => true,
        ]);

        $found = CalibrationPoint::find($point->id);

        expect($found->nominal_value)->toBe('100.500000')
            ->and($found->unit)->toBe('mm')
            ->and($found->pass)->toBeTrue();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-03: RLS
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-03: RLS nas tabelas do Slice 005', function (): void {
    it('[AC-005-03] política tenant_isolation existe em service_orders', function (): void {
        $policies = DB::select("
            SELECT p.polname FROM pg_policy p
            JOIN pg_class c ON c.oid = p.polrelid
            WHERE c.relname = 'service_orders' AND p.polname = 'tenant_isolation'
        ");

        expect($policies)->not->toBeEmpty();
    })->skip(fn (): bool => config('database.default') !== 'pgsql', 'Requer PostgreSQL');

    it('[AC-005-03] política tenant_isolation existe em calibrations', function (): void {
        $policies = DB::select("
            SELECT p.polname FROM pg_policy p
            JOIN pg_class c ON c.oid = p.polrelid
            WHERE c.relname = 'calibrations' AND p.polname = 'tenant_isolation'
        ");

        expect($policies)->not->toBeEmpty();
    })->skip(fn (): bool => config('database.default') !== 'pgsql', 'Requer PostgreSQL');

    it('[AC-005-03] FORCE ROW LEVEL SECURITY habilitado em service_orders', function (): void {
        $result = DB::select("SELECT relrowsecurity, relforcerowsecurity FROM pg_class WHERE relname = 'service_orders'");

        expect((bool) $result[0]->relrowsecurity)->toBeTrue()
            ->and((bool) $result[0]->relforcerowsecurity)->toBeTrue();
    })->skip(fn (): bool => config('database.default') !== 'pgsql', 'Requer PostgreSQL');

    it('[AC-005-03] ServiceOrder de Tenant A invisível para Tenant B via Eloquent', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        $os = ServiceOrder::factory()->create(['tenant_id' => $tenantA->id]);

        TenantContext::set($tenantB->id);
        $found = ServiceOrder::find($os->id);

        expect($found)->toBeNull();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-04: Máquina de estados
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-04: máquina de estados — ServiceOrder', function (): void {
    it('[AC-005-04] ServiceOrder segue fluxo linear válido', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'status' => ServiceOrderStatus::Draft->value]);

        $os->changeStatus(ServiceOrderStatus::Open);
        expect($os->status)->toBe(ServiceOrderStatus::Open);

        $os->changeStatus(ServiceOrderStatus::InProgress);
        expect($os->status)->toBe(ServiceOrderStatus::InProgress);

        $os->changeStatus(ServiceOrderStatus::PendingReview);
        expect($os->status)->toBe(ServiceOrderStatus::PendingReview);

        $os->changeStatus(ServiceOrderStatus::Completed);
        expect($os->status)->toBe(ServiceOrderStatus::Completed);
    });

    it('[AC-005-04] ServiceOrder lança exceção ao pular estado', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'status' => ServiceOrderStatus::Draft->value]);

        expect(fn () => $os->changeStatus(ServiceOrderStatus::InProgress))
            ->toThrow(LogicException::class);
    });

    it('[AC-005-04] ServiceOrder pode voltar de PendingReview para InProgress', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'status' => ServiceOrderStatus::PendingReview->value]);

        $os->changeStatus(ServiceOrderStatus::InProgress);
        expect($os->status)->toBe(ServiceOrderStatus::InProgress);
    });

    it('[AC-005-04] ServiceOrder Closed não permite mais transições', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'status' => ServiceOrderStatus::Closed->value]);

        expect($os->status->allowedTransitions())->toBeEmpty();
        expect(fn () => $os->changeStatus(ServiceOrderStatus::Draft))->toThrow(LogicException::class);
    });
});

describe('AC-005-04: máquina de estados — Calibration', function (): void {
    it('[AC-005-04] Calibration segue fluxo draft → in_progress via start()', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->start($fixture['executor']);

        expect($fixture['calibration']->status)->toBe(CalibrationStatus::InProgress)
            ->and($fixture['calibration']->executor_id)->toBe($fixture['executor']->id)
            ->and($fixture['calibration']->started_at)->not->toBeNull();
    });

    it('[AC-005-04] Calibration lança exceção ao pular estado', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        expect(fn () => $fixture['calibration']->submitForReview())->toThrow(LogicException::class);
    });

    it('[AC-005-04] Calibration segue fluxo completo até issued', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->start($fixture['executor']);
        $fixture['calibration']->submitForReview();
        $fixture['calibration']->approve($fixture['verifier']);
        $fixture['calibration']->issue();

        expect($fixture['calibration']->status)->toBe(CalibrationStatus::Issued)
            ->and($fixture['calibration']->certificate_number)->toStartWith('CERT-');
    });

    it('[AC-005-04] Calibration pode ser cancelada em qualquer estado intermediário', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->start($fixture['executor']);
        $fixture['calibration']->cancel();

        expect($fixture['calibration']->status)->toBe(CalibrationStatus::Cancelled);
    });

    it('[AC-005-04] Calibration Issued não pode ser cancelada', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->start($fixture['executor']);
        $fixture['calibration']->submitForReview();
        $fixture['calibration']->approve($fixture['verifier']);
        $fixture['calibration']->issue();

        expect(fn () => $fixture['calibration']->cancel())->toThrow(LogicException::class);
    });

    it('[AC-005-04] CalibrationStatus::values() retorna todos os valores', function (): void {
        expect(CalibrationStatus::values())->toHaveCount(6);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-05: Dual sign-off
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-05: dual sign-off ISO 17025', function (): void {
    it('[AC-005-05] approve() lança exceção se verificador = executor', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->start($fixture['executor']);
        $fixture['calibration']->submitForReview();

        // Same user tries to approve their own calibration
        expect(fn () => $fixture['calibration']->approve($fixture['executor']))->toThrow(LogicException::class);
    });

    it('[AC-005-05] approve() funciona com verificador diferente', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->start($fixture['executor']);
        $fixture['calibration']->submitForReview();
        $fixture['calibration']->approve($fixture['verifier']);

        expect($fixture['calibration']->status)->toBe(CalibrationStatus::Approved)
            ->and($fixture['calibration']->verifier_id)->toBe($fixture['verifier']->id)
            ->and($fixture['calibration']->completed_at)->not->toBeNull();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-06: Competency gate
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-06: gate de competência do técnico', function (): void {
    it('[AC-005-06] start() bloqueia técnico sem competência no domínio', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $techWithoutCompetency = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => Role::Tecnico->value,
        ]);

        $client = Client::factory()->forTenant($tenant)->create();
        $instrument = Instrument::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'domain' => Domain::Dimensional->value,
        ]);
        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id]);
        $calibration = Calibration::factory()->create([
            'tenant_id' => $tenant->id,
            'service_order_id' => $os->id,
            'instrument_id' => $instrument->id,
        ]);

        expect(fn () => $calibration->start($techWithoutCompetency))->toThrow(LogicException::class);
    });

    it('[AC-005-06] start() bloqueia técnico com competência vencida', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $tech = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);
        TechnicianCompetency::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $tech->id,
            'domain' => Domain::Dimensional->value,
            'qualified_at' => Carbon::now()->subYears(2),
            'expires_at' => Carbon::now()->subDay(), // expired yesterday
        ]);

        $client = Client::factory()->forTenant($tenant)->create();
        $instrument = Instrument::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'domain' => Domain::Dimensional->value,
        ]);
        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id]);
        $calibration = Calibration::factory()->create([
            'tenant_id' => $tenant->id,
            'service_order_id' => $os->id,
            'instrument_id' => $instrument->id,
        ]);

        expect(fn () => $calibration->start($tech))->toThrow(LogicException::class);
    });

    it('[AC-005-06] start() bloqueia técnico com competência no domínio errado', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $tech = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);
        // Competency for Pressao, but instrument is Dimensional
        TechnicianCompetency::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $tech->id,
            'domain' => Domain::Pressao->value,
            'qualified_at' => Carbon::now()->subYear(),
            'expires_at' => Carbon::now()->addYear(),
        ]);

        $client = Client::factory()->forTenant($tenant)->create();
        $instrument = Instrument::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'domain' => Domain::Dimensional->value,
        ]);
        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id]);
        $calibration = Calibration::factory()->create([
            'tenant_id' => $tenant->id,
            'service_order_id' => $os->id,
            'instrument_id' => $instrument->id,
        ]);

        expect(fn () => $calibration->start($tech))->toThrow(LogicException::class);
    });

    it('[AC-005-06] approve() bloqueia verificador sem competência', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->start($fixture['executor']);
        $fixture['calibration']->submitForReview();

        $verifierNoComp = User::factory()->create([
            'tenant_id' => $fixture['tenant']->id,
            'role' => Role::Tecnico->value,
        ]);

        expect(fn () => $fixture['calibration']->approve($verifierNoComp))->toThrow(LogicException::class);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-07: Standard validity gate
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-07: gate de validade do padrão', function (): void {
    it('[AC-005-07] start() bloqueia quando o padrão está com certificado vencido', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $domain = Domain::Dimensional;
        $tech = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);
        TechnicianCompetency::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $tech->id,
            'domain' => $domain->value,
            'expires_at' => Carbon::now()->addYear(),
        ]);

        $client = Client::factory()->forTenant($tenant)->create();
        $instrument = Instrument::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'domain' => $domain->value,
        ]);
        $expiredStandard = Standard::factory()->expired()->create([
            'tenant_id' => $tenant->id,
            'domain' => $domain->value,
        ]);
        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id]);
        $calibration = Calibration::factory()->create([
            'tenant_id' => $tenant->id,
            'service_order_id' => $os->id,
            'instrument_id' => $instrument->id,
            'standard_id' => $expiredStandard->id,
        ]);

        expect(fn () => $calibration->start($tech))->toThrow(LogicException::class);
    });

    it('[AC-005-07] start() prossegue quando padrão é válido', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->start($fixture['executor']);

        expect($fixture['calibration']->status)->toBe(CalibrationStatus::InProgress);
    });

    it('[AC-005-07] start() prossegue sem padrão (standard_id null)', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $domain = Domain::Dimensional;
        $tech = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);
        TechnicianCompetency::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $tech->id,
            'domain' => $domain->value,
            'expires_at' => Carbon::now()->addYear(),
        ]);

        $client = Client::factory()->forTenant($tenant)->create();
        $instrument = Instrument::factory()->create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'domain' => $domain->value,
        ]);
        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id]);
        $calibration = Calibration::factory()->create([
            'tenant_id' => $tenant->id,
            'service_order_id' => $os->id,
            'instrument_id' => $instrument->id,
            'standard_id' => null,
        ]);

        $calibration->start($tech);
        expect($calibration->status)->toBe(CalibrationStatus::InProgress);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-09: Policies
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-09: políticas de autorização — ServiceOrder', function (): void {
    it('[AC-005-09] gerente pode criar OS', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $gerente = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Gerente->value]);
        $policy = new ServiceOrderPolicy;

        expect($policy->create($gerente))->toBeTrue();
    });

    it('[AC-005-09] tecnico não pode criar OS', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $tech = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);
        $policy = new ServiceOrderPolicy;

        expect($policy->create($tech))->toBeFalse();
    });

    it('[AC-005-09] tecnico atribuído pode atualizar OS', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $tech = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);
        $os = ServiceOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'assigned_technician_id' => $tech->id,
        ]);
        $policy = new ServiceOrderPolicy;

        expect($policy->update($tech, $os))->toBeTrue();
    });

    it('[AC-005-09] tecnico não atribuído não pode atualizar OS', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $tech = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);
        $os = ServiceOrder::factory()->create([
            'tenant_id' => $tenant->id,
            'assigned_technician_id' => null,
        ]);
        $policy = new ServiceOrderPolicy;

        expect($policy->update($tech, $os))->toBeFalse();
    });

    it('[AC-005-09] somente gerente pode deletar OS', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $gerente = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Gerente->value]);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Administrativo->value]);
        $os = ServiceOrder::factory()->create(['tenant_id' => $tenant->id]);
        $policy = new ServiceOrderPolicy;

        expect($policy->delete($gerente, $os))->toBeTrue()
            ->and($policy->delete($admin, $os))->toBeFalse();
    });
});

describe('AC-005-09: políticas de autorização — Calibration', function (): void {
    it('[AC-005-09] tecnico pode criar calibração', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $tech = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);
        $policy = new CalibrationPolicy;

        expect($policy->create($tech))->toBeTrue();
    });

    it('[AC-005-09] executor pode atualizar própria calibração', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->executor_id = $fixture['executor']->id;
        $fixture['calibration']->save();
        $policy = new CalibrationPolicy;

        expect($policy->update($fixture['executor'], $fixture['calibration']))->toBeTrue();
    });

    it('[AC-005-09] verificador não-executor pode aprovar calibração', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->executor_id = $fixture['executor']->id;
        $fixture['calibration']->save();
        $policy = new CalibrationPolicy;

        expect($policy->approve($fixture['verifier'], $fixture['calibration']))->toBeTrue()
            ->and($policy->approve($fixture['executor'], $fixture['calibration']))->toBeFalse();
    });

    it('[AC-005-09] apenas gerente/admin pode emitir certificado', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $gerente = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Gerente->value]);
        $tech = User::factory()->create(['tenant_id' => $tenant->id, 'role' => Role::Tecnico->value]);

        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);
        $policy = new CalibrationPolicy;

        $gerenteOfFixtureTenant = User::factory()->create(['tenant_id' => $fixture['tenant']->id, 'role' => Role::Gerente->value]);
        expect($policy->issue($gerenteOfFixtureTenant, $fixture['calibration']))->toBeTrue()
            ->and($policy->issue($fixture['executor'], $fixture['calibration']))->toBeFalse();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-10: Factories
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-10: factories', function (): void {
    it('[AC-005-10] ServiceOrderFactory cria OS com dados válidos', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $os = ServiceOrder::factory()->forTenant($tenant)->create();

        expect($os->id)->not->toBeNull()
            ->and($os->status)->toBeInstanceOf(ServiceOrderStatus::class)
            ->and($os->mode)->toBeInstanceOf(ServiceOrderMode::class);
    });

    it('[AC-005-10] ServiceOrderFactory::open() cria OS em status Open', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $os = ServiceOrder::factory()->forTenant($tenant)->open()->create();

        expect($os->status)->toBe(ServiceOrderStatus::Open);
    });

    it('[AC-005-10] CalibrationFactory cria calibração com status draft', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        expect($fixture['calibration']->status)->toBe(CalibrationStatus::Draft);
    });

    it('[AC-005-10] CalibrationPointFactory::passing() cria ponto conforme', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $point = CalibrationPoint::factory()->forCalibration($fixture['calibration'])->passing()->create();

        expect($point->pass)->toBeTrue();
    });

    it('[AC-005-10] CalibrationPointFactory::failing() cria ponto não conforme', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $point = CalibrationPoint::factory()->forCalibration($fixture['calibration'])->failing()->create();

        expect($point->pass)->toBeFalse();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-02: Relacionamentos
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-02: relacionamentos', function (): void {
    it('[AC-005-02] ServiceOrder belongs to Client', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        expect($fixture['os']->client)->toBeInstanceOf(Client::class);
    });

    it('[AC-005-02] ServiceOrder has many Calibrations', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        expect($fixture['os']->calibrations)->toHaveCount(1)
            ->and($fixture['os']->calibrations->first())->toBeInstanceOf(Calibration::class);
    });

    it('[AC-005-02] Calibration belongs to Instrument, Standard, Procedure', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        expect($fixture['calibration']->instrument)->toBeInstanceOf(Instrument::class)
            ->and($fixture['calibration']->standard)->toBeInstanceOf(Standard::class)
            ->and($fixture['calibration']->procedure)->toBeInstanceOf(Procedure::class);
    });

    it('[AC-005-02] CalibrationPoint belongs to Calibration', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $point = CalibrationPoint::factory()->forCalibration($fixture['calibration'])->create();

        expect($point->calibration)->toBeInstanceOf(Calibration::class)
            ->and($point->calibration->id)->toBe($fixture['calibration']->id);
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-02: Auto-numbering
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-02: auto-numeração', function (): void {
    it('[AC-005-02] ServiceOrder recebe número único no formato OS-YYYY-NNNN', function (): void {
        $tenant = Tenant::factory()->create();
        TenantContext::set($tenant->id);

        $os1 = ServiceOrder::factory()->forTenant($tenant)->create();
        $os2 = ServiceOrder::factory()->forTenant($tenant)->create();

        expect($os1->number)->toMatch('/^OS-\d{4}-\d{4}$/')
            ->and($os2->number)->toMatch('/^OS-\d{4}-\d{4}$/')
            ->and($os1->number)->not->toBe($os2->number);
    });

    it('[AC-005-02] número de OS é único por tenant (distinct numbering per tenant)', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        $osA = ServiceOrder::factory()->forTenant($tenantA)->create();

        TenantContext::set($tenantB->id);
        $osB = ServiceOrder::factory()->forTenant($tenantB)->create();

        // Both can have same OS number (per-tenant sequence)
        expect($osA->number)->toMatch('/^OS-\d{4}-\d{4}$/')
            ->and($osB->number)->toMatch('/^OS-\d{4}-\d{4}$/');
    });

    it('[AC-005-02] certificado recebe número único no formato CERT-YYYY-NNNN', function (): void {
        $fixture = makeCalibrationFixture();
        TenantContext::set($fixture['tenant']->id);

        $fixture['calibration']->start($fixture['executor']);
        $fixture['calibration']->submitForReview();
        $fixture['calibration']->approve($fixture['verifier']);
        $fixture['calibration']->issue();

        expect($fixture['calibration']->certificate_number)->toMatch('/^CERT-\d{4}-\d{4}$/');
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-005-03: Tenant isolation — Calibration
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-005-03: isolamento de tenant — Calibration', function (): void {
    it('[AC-005-03] Calibration de Tenant A invisível para Tenant B via Eloquent', function (): void {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        TenantContext::set($tenantA->id);
        $clientA = Client::factory()->forTenant($tenantA)->create();
        $instA = Instrument::factory()->create(['tenant_id' => $tenantA->id, 'client_id' => $clientA->id]);
        $osA = ServiceOrder::factory()->create(['tenant_id' => $tenantA->id, 'client_id' => $clientA->id]);
        $calibration = Calibration::factory()->create([
            'tenant_id' => $tenantA->id,
            'service_order_id' => $osA->id,
            'instrument_id' => $instA->id,
        ]);

        TenantContext::set($tenantB->id);
        $found = Calibration::find($calibration->id);

        expect($found)->toBeNull();
    });
});
