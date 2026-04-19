<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standards', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->string('serial_number');
            $table->text('description')->nullable();
            $table->string('certificate_number');
            $table->date('certificate_date');
            $table->date('validity_date');
            $table->string('domain');
            $table->decimal('drift_tolerance', 15, 6)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'domain']);
            $table->index(['tenant_id', 'validity_date']);
            $table->unique(['tenant_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standards');
    }
};
