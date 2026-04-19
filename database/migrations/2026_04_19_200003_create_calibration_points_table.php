<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calibration_points', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('calibration_id');
            $table->foreign('calibration_id')->references('id')->on('calibrations')->cascadeOnDelete();

            $table->decimal('nominal_value', 15, 6);
            $table->decimal('measured_value', 15, 6);
            $table->string('unit', 50);
            $table->decimal('deviation', 15, 6);
            $table->decimal('uncertainty', 15, 6);
            $table->boolean('pass');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibration_points');
    }
};
