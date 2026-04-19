<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enable PostgreSQL Row-Level Security on all Slice 004 domain tables.
 *
 * ADR-0016 Layer 3: same pattern as users/audits RLS migration.
 * Tables: clients, instruments, standards, procedures, technician_competencies.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'clients',
        'instruments',
        'standards',
        'procedures',
        'technician_competencies',
    ];

    public function up(): void
    {
        DB::unprepared('GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO kalibrium_app');
        DB::unprepared('GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO kalibrium_app');

        foreach ($this->tables as $table) {
            DB::unprepared("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::unprepared("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::unprepared("
                CREATE POLICY tenant_isolation ON {$table}
                USING (
                    tenant_id = CAST(
                        NULLIF(current_setting('app.current_tenant_id', true), '') AS uuid
                    )
                )
                WITH CHECK (
                    tenant_id = CAST(
                        NULLIF(current_setting('app.current_tenant_id', true), '') AS uuid
                    )
                )
            ");
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            DB::unprepared("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::unprepared("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
