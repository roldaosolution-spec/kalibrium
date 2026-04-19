<?php

namespace Database\Seeders;

use App\Support\TenantContext;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // KAL-20 F4: guard against running demo seed data in production.
        // admin@kalibrium.demo with a known password must never be active in prod.
        if (app()->isProduction()) {
            return;
        }

        $tenant = Tenant::factory()->create([
            'name' => 'Kalibrium Demo',
            'slug' => 'kalibrium-demo',
            'cnpj' => '00.000.000/0001-00',
            'status' => 'active',
            'settings' => ['timezone' => 'America/Sao_Paulo', 'locale' => 'pt_BR'],
        ]);

        TenantContext::set($tenant->id);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Administrador Demo',
            'email' => 'admin@kalibrium.demo',
        ]);

        TenantContext::clear();
    }
}
