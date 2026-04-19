<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // calibrations: FK columns without indices cause full scans when joining/filtering
        Schema::table('calibrations', function (Blueprint $table): void {
            $table->index('instrument_id');
            $table->index('standard_id');
            $table->index('procedure_id');
            $table->index('executor_id');
            $table->index('verifier_id');
        });

        // calibration_points: $calibration->points() does a full scan without this
        Schema::table('calibration_points', function (Blueprint $table): void {
            $table->index('calibration_id');
        });

        // service_orders: technician workload queries lack an index
        Schema::table('service_orders', function (Blueprint $table): void {
            $table->index('assigned_technician_id');
        });
    }

    public function down(): void
    {
        Schema::table('calibrations', function (Blueprint $table): void {
            $table->dropIndex(['instrument_id']);
            $table->dropIndex(['standard_id']);
            $table->dropIndex(['procedure_id']);
            $table->dropIndex(['executor_id']);
            $table->dropIndex(['verifier_id']);
        });

        Schema::table('calibration_points', function (Blueprint $table): void {
            $table->dropIndex(['calibration_id']);
        });

        Schema::table('service_orders', function (Blueprint $table): void {
            $table->dropIndex(['assigned_technician_id']);
        });
    }
};
