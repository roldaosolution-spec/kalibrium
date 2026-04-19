<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calibration_points', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable()->after('calibration_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });

        DB::statement(<<<'SQL'
            UPDATE calibration_points cp
            SET tenant_id = c.tenant_id
            FROM calibrations c
            WHERE cp.calibration_id = c.id
        SQL);

        Schema::table('calibration_points', function (Blueprint $table): void {
            $table->uuid('tenant_id')->nullable(false)->change();
        });

        DB::unprepared("ALTER TABLE calibration_points ENABLE ROW LEVEL SECURITY");
        DB::unprepared("ALTER TABLE calibration_points FORCE ROW LEVEL SECURITY");
        DB::unprepared("
            CREATE POLICY tenant_isolation ON calibration_points
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

    public function down(): void
    {
        DB::unprepared("DROP POLICY IF EXISTS tenant_isolation ON calibration_points");
        DB::unprepared("ALTER TABLE calibration_points DISABLE ROW LEVEL SECURITY");

        Schema::table('calibration_points', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
