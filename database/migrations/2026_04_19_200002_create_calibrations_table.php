<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calibrations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->uuid('service_order_id');
            $table->foreign('service_order_id')->references('id')->on('service_orders')->cascadeOnDelete();
            $table->uuid('instrument_id');
            $table->foreign('instrument_id')->references('id')->on('instruments');
            $table->uuid('standard_id')->nullable();
            $table->foreign('standard_id')->references('id')->on('standards')->nullOnDelete();
            $table->uuid('procedure_id')->nullable();
            $table->foreign('procedure_id')->references('id')->on('procedures')->nullOnDelete();
            $table->unsignedBigInteger('executor_id')->nullable();
            $table->foreign('executor_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('verifier_id')->nullable();
            $table->foreign('verifier_id')->references('id')->on('users')->nullOnDelete();

            $table->string('status', 20)->default('draft');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('certificate_number', 30)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'service_order_id']);
            $table->unique(['tenant_id', 'certificate_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibrations');
    }
};
