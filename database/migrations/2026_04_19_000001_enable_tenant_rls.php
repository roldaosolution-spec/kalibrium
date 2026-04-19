<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enable PostgreSQL Row-Level Security (RLS) on all tenant-scoped tables.
 *
 * ADR-0016 Layer 3: enforce at DB level so even raw SQL cannot bypass tenant isolation.
 * The policy uses app.current_tenant_id (set by SetTenantContext middleware per request).
 *
 * kalibrium_app is the non-superuser application role. Only this role is subject to RLS.
 * The kalibrium superuser (used for migrations/maintenance) bypasses RLS automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Create the non-superuser application role used by the app at runtime.
        // Superusers always bypass RLS; kalibrium_app does not.
        DB::unprepared("
            DO \$\$ BEGIN
                IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'kalibrium_app') THEN
                    CREATE ROLE kalibrium_app NOINHERIT NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION;
                END IF;
            END \$\$
        ");

        DB::unprepared('GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO kalibrium_app');
        DB::unprepared('GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO kalibrium_app');

        // ── users table ────────────────────────────────────────────────────────
        DB::unprepared('ALTER TABLE users ENABLE ROW LEVEL SECURITY');

        // FORCE RLS so even the table owner (when not superuser) is subject to it.
        DB::unprepared('ALTER TABLE users FORCE ROW LEVEL SECURITY');

        // Policy: allow access only to rows belonging to the current tenant.
        // NULLIF handles the case where app.current_tenant_id is not yet set —
        // an empty string cast to uuid would raise an error, so we cast NULL instead,
        // which makes tenant_id = NULL evaluate to false (deny all rows securely).
        DB::unprepared("
            CREATE POLICY tenant_isolation ON users
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

        // ── audits table ───────────────────────────────────────────────────────
        // Audits have a nullable tenant_id (cross-tenant audit entries are allowed for
        // superadmin ops). NULL rows are visible to all; scoped rows only to their tenant.
        DB::unprepared('ALTER TABLE audits ENABLE ROW LEVEL SECURITY');
        DB::unprepared('ALTER TABLE audits FORCE ROW LEVEL SECURITY');

        DB::unprepared("
            CREATE POLICY tenant_isolation ON audits
            USING (
                tenant_id IS NULL
                OR tenant_id = CAST(
                    NULLIF(current_setting('app.current_tenant_id', true), '') AS uuid
                )
            )
            WITH CHECK (
                tenant_id IS NULL
                OR tenant_id = CAST(
                    NULLIF(current_setting('app.current_tenant_id', true), '') AS uuid
                )
            )
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP POLICY IF EXISTS tenant_isolation ON audits');
        DB::unprepared('ALTER TABLE audits DISABLE ROW LEVEL SECURITY');

        DB::unprepared('DROP POLICY IF EXISTS tenant_isolation ON users');
        DB::unprepared('ALTER TABLE users DISABLE ROW LEVEL SECURITY');

        DB::unprepared("
            DO \$\$ BEGIN
                IF EXISTS (SELECT FROM pg_roles WHERE rolname = 'kalibrium_app') THEN
                    REVOKE ALL ON ALL TABLES IN SCHEMA public FROM kalibrium_app;
                    REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM kalibrium_app;
                    DROP ROLE kalibrium_app;
                END IF;
            END \$\$
        ");
    }
};
