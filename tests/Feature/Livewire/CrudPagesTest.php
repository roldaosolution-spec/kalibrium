<?php

declare(strict_types=1);

use App\Enums\Domain;
use App\Enums\Role;
use App\Livewire\Clients\ClientForm;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Instruments\InstrumentForm;
use App\Livewire\Instruments\InstrumentIndex;
use App\Models\Client;
use App\Models\Instrument;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Livewire\Livewire;

afterEach(function (): void {
    TenantContext::clear();
});

// ──────────────────────────────────────────────────────────────────────────────
// Helper
// ──────────────────────────────────────────────────────────────────────────────

function makeCrudFixture(string $role = 'gerente'): array
{
    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant->id);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => $role]);

    return ['tenant' => $tenant, 'user' => $user];
}

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-06: ClientIndex
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-06: ClientIndex', function (): void {
    it('[AC-004-06] lista apenas clientes do tenant correto', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture();
        Client::factory()->forTenant($tenant)->create(['name' => 'Empresa Alfa']);

        $other = Tenant::factory()->create();
        TenantContext::set($other->id);
        Client::factory()->forTenant($other)->create(['name' => 'Empresa Estranha']);
        TenantContext::set($tenant->id);

        Livewire::actingAs($user)
            ->test(ClientIndex::class)
            ->assertSee('Empresa Alfa')
            ->assertDontSee('Empresa Estranha');
    });

    it('[AC-004-06] busca filtra por nome', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture();
        Client::factory()->forTenant($tenant)->create(['name' => 'Alfa Sistemas']);
        Client::factory()->forTenant($tenant)->create(['name' => 'Beta Tecnologia']);

        Livewire::actingAs($user)
            ->test(ClientIndex::class)
            ->set('search', 'Alfa')
            ->assertSee('Alfa Sistemas')
            ->assertDontSee('Beta Tecnologia');
    });

    it('[AC-004-06] busca filtra por CNPJ', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture();
        Client::factory()->forTenant($tenant)->create(['name' => 'Empresa X', 'cnpj' => '11.111.111/0001-11']);
        Client::factory()->forTenant($tenant)->create(['name' => 'Empresa Y', 'cnpj' => '22.222.222/0001-22']);

        Livewire::actingAs($user)
            ->test(ClientIndex::class)
            ->set('search', '11.111')
            ->assertSee('Empresa X')
            ->assertDontSee('Empresa Y');
    });

    it('[AC-004-06] Gerente pode excluir cliente', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Gerente->value);
        $client = Client::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(ClientIndex::class)
            ->call('delete', $client->id)
            ->assertHasNoErrors();

        expect(Client::withTrashed()->find($client->id)->deleted_at)->not->toBeNull();
    });

    it('[AC-004-06] Tecnico nao pode excluir cliente', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Tecnico->value);
        $client = Client::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(ClientIndex::class)
            ->call('delete', $client->id)
            ->assertForbidden();
    });

    it('[AC-004-06] Administrativo nao pode excluir cliente', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Administrativo->value);
        $client = Client::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(ClientIndex::class)
            ->call('delete', $client->id)
            ->assertForbidden();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-06: ClientForm
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-06: ClientForm', function (): void {
    it('[AC-004-06] Gerente pode abrir formulario de criacao', function (): void {
        ['user' => $user] = makeCrudFixture(Role::Gerente->value);

        Livewire::actingAs($user)
            ->test(ClientForm::class)
            ->assertOk();
    });

    it('[AC-004-06] Administrativo pode abrir formulario de criacao', function (): void {
        ['user' => $user] = makeCrudFixture(Role::Administrativo->value);

        Livewire::actingAs($user)
            ->test(ClientForm::class)
            ->assertOk();
    });

    it('[AC-004-06] Tecnico nao pode abrir formulario de criacao', function (): void {
        ['user' => $user] = makeCrudFixture(Role::Tecnico->value);

        Livewire::actingAs($user)
            ->test(ClientForm::class)
            ->assertForbidden();
    });

    it('[AC-004-06] save cria cliente com dados validos', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Gerente->value);

        Livewire::actingAs($user)
            ->test(ClientForm::class)
            ->set('name', 'Nova Empresa')
            ->set('cnpj', '12.345.678/0001-90')
            ->set('email', 'contato@nova.com.br')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('clients.index'));

        expect(Client::where('name', 'Nova Empresa')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    });

    it('[AC-004-06] save falha quando nome e vazio', function (): void {
        ['user' => $user] = makeCrudFixture(Role::Gerente->value);

        Livewire::actingAs($user)
            ->test(ClientForm::class)
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    });

    it('[AC-004-06] mount carrega dados do cliente para edicao', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Gerente->value);
        $client = Client::factory()->forTenant($tenant)->create([
            'name' => 'Cliente Existente',
            'cnpj' => '98.765.432/0001-10',
            'address' => 'Rua Teste, 123',
        ]);

        Livewire::actingAs($user)
            ->test(ClientForm::class, ['id' => $client->id])
            ->assertSet('name', 'Cliente Existente')
            ->assertSet('cnpj', '98.765.432/0001-10')
            ->assertSet('address', 'Rua Teste, 123');
    });

    it('[AC-004-06] Tecnico nao pode abrir formulario de edicao', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Tecnico->value);
        $client = Client::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(ClientForm::class, ['id' => $client->id])
            ->assertForbidden();
    });

    it('[AC-004-06] save atualiza cliente existente', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Gerente->value);
        $client = Client::factory()->forTenant($tenant)->create(['name' => 'Nome Antigo']);

        Livewire::actingAs($user)
            ->test(ClientForm::class, ['id' => $client->id])
            ->set('name', 'Nome Atualizado')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('clients.index'));

        expect($client->fresh()->name)->toBe('Nome Atualizado');
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-06: InstrumentIndex
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-06: InstrumentIndex', function (): void {
    it('[AC-004-06] lista apenas instrumentos do tenant correto', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture();
        Instrument::factory()->create(['tenant_id' => $tenant->id, 'serial_number' => 'SN-MEUTENANT']);

        $other = Tenant::factory()->create();
        TenantContext::set($other->id);
        Instrument::factory()->create(['tenant_id' => $other->id, 'serial_number' => 'SN-OUTRO']);
        TenantContext::set($tenant->id);

        Livewire::actingAs($user)
            ->test(InstrumentIndex::class)
            ->assertSee('SN-MEUTENANT')
            ->assertDontSee('SN-OUTRO');
    });

    it('[AC-004-06] filtro por dominio funciona', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture();
        Instrument::factory()->create(['tenant_id' => $tenant->id, 'serial_number' => 'SN-DIM', 'domain' => Domain::Dimensional->value]);
        Instrument::factory()->create(['tenant_id' => $tenant->id, 'serial_number' => 'SN-MASSA', 'domain' => Domain::Massa->value]);

        Livewire::actingAs($user)
            ->test(InstrumentIndex::class)
            ->set('domainFilter', Domain::Dimensional->value)
            ->assertSee('SN-DIM')
            ->assertDontSee('SN-MASSA');
    });

    it('[AC-004-06] busca filtra por numero de serie', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture();
        Instrument::factory()->create(['tenant_id' => $tenant->id, 'serial_number' => 'ALFA-001']);
        Instrument::factory()->create(['tenant_id' => $tenant->id, 'serial_number' => 'BETA-002']);

        Livewire::actingAs($user)
            ->test(InstrumentIndex::class)
            ->set('search', 'ALFA')
            ->assertSee('ALFA-001')
            ->assertDontSee('BETA-002');
    });

    it('[AC-004-06] Gerente pode excluir instrumento', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Gerente->value);
        $instrument = Instrument::factory()->create(['tenant_id' => $tenant->id]);

        Livewire::actingAs($user)
            ->test(InstrumentIndex::class)
            ->call('delete', $instrument->id)
            ->assertHasNoErrors();

        expect(Instrument::withTrashed()->find($instrument->id)->deleted_at)->not->toBeNull();
    });

    it('[AC-004-06] Tecnico nao pode excluir instrumento', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Tecnico->value);
        $instrument = Instrument::factory()->create(['tenant_id' => $tenant->id]);

        Livewire::actingAs($user)
            ->test(InstrumentIndex::class)
            ->call('delete', $instrument->id)
            ->assertForbidden();
    });
});

// ──────────────────────────────────────────────────────────────────────────────
// AC-004-06: InstrumentForm
// ──────────────────────────────────────────────────────────────────────────────

describe('AC-004-06: InstrumentForm', function (): void {
    it('[AC-004-06] Gerente pode abrir formulario de criacao de instrumento', function (): void {
        ['user' => $user] = makeCrudFixture(Role::Gerente->value);

        Livewire::actingAs($user)
            ->test(InstrumentForm::class)
            ->assertOk();
    });

    it('[AC-004-06] Tecnico nao pode abrir formulario de criacao de instrumento', function (): void {
        ['user' => $user] = makeCrudFixture(Role::Tecnico->value);

        Livewire::actingAs($user)
            ->test(InstrumentForm::class)
            ->assertForbidden();
    });

    it('[AC-004-06] save cria instrumento com dados validos', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Gerente->value);
        $client = Client::factory()->forTenant($tenant)->create();

        Livewire::actingAs($user)
            ->test(InstrumentForm::class)
            ->set('serial_number', 'SN-NOVO-001')
            ->set('type', 'Paquímetro')
            ->set('domain', Domain::Dimensional->value)
            ->set('range_min', '0')
            ->set('range_max', '150')
            ->set('resolution', '0.01')
            ->set('client_id', $client->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('instruments.index'));

        expect(Instrument::where('serial_number', 'SN-NOVO-001')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    });

    it('[AC-004-06] save falha com dominio invalido', function (): void {
        ['user' => $user] = makeCrudFixture(Role::Gerente->value);

        Livewire::actingAs($user)
            ->test(InstrumentForm::class)
            ->set('serial_number', 'SN-999')
            ->set('type', 'Termômetro')
            ->set('domain', 'invalido')
            ->call('save')
            ->assertHasErrors(['domain']);
    });

    it('[AC-004-06] save falha sem numero de serie', function (): void {
        ['user' => $user] = makeCrudFixture(Role::Gerente->value);

        Livewire::actingAs($user)
            ->test(InstrumentForm::class)
            ->set('serial_number', '')
            ->set('type', 'Manômetro')
            ->set('domain', Domain::Pressao->value)
            ->call('save')
            ->assertHasErrors(['serial_number' => 'required']);
    });

    it('[AC-004-06] mount carrega dados do instrumento para edicao', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Gerente->value);
        $instrument = Instrument::factory()->create([
            'tenant_id' => $tenant->id,
            'serial_number' => 'SN-EDIT-99',
            'type' => 'Micrômetro',
            'domain' => Domain::Dimensional->value,
        ]);

        Livewire::actingAs($user)
            ->test(InstrumentForm::class, ['id' => $instrument->id])
            ->assertSet('serial_number', 'SN-EDIT-99')
            ->assertSet('type', 'Micrômetro')
            ->assertSet('domain', Domain::Dimensional->value);
    });

    it('[AC-004-06] Tecnico nao pode abrir formulario de edicao de instrumento', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Tecnico->value);
        $instrument = Instrument::factory()->create(['tenant_id' => $tenant->id]);

        Livewire::actingAs($user)
            ->test(InstrumentForm::class, ['id' => $instrument->id])
            ->assertForbidden();
    });

    it('[AC-004-06] save atualiza instrumento existente', function (): void {
        ['tenant' => $tenant, 'user' => $user] = makeCrudFixture(Role::Gerente->value);
        $instrument = Instrument::factory()->create([
            'tenant_id' => $tenant->id,
            'serial_number' => 'SN-OLD',
            'type' => 'Balança',
            'domain' => Domain::Massa->value,
        ]);

        Livewire::actingAs($user)
            ->test(InstrumentForm::class, ['id' => $instrument->id])
            ->set('serial_number', 'SN-UPDATED')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('instruments.index'));

        expect($instrument->fresh()->serial_number)->toBe('SN-UPDATED');
    });
});
