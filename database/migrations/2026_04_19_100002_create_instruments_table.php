<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->uuid('client_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();

            $table->string('serial_number');
            $table->string('type');
            $table->text('description')->nullable();
            $table->decimal('range_min', 15, 6)->nullable();
            $table->decimal('range_max', 15, 6)->nullable();
            $table->decimal('resolution', 15, 6)->nullable();
            $table->string('domain');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'domain']);
            $table->index(['tenant_id', 'client_id']);
            $table->unique(['tenant_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};
