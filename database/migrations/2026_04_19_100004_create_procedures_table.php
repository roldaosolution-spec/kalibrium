<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->string('code');
            $table->string('title');
            $table->string('domain');
            $table->string('revision', 10)->default('00');
            $table->json('steps')->nullable();
            $table->text('uncertainty_formula')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'domain']);
            $table->unique(['tenant_id', 'code', 'revision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
