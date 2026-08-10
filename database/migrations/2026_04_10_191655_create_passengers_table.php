<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_id');
            $table->boolean('is_patient');
            $table->foreignId('patient_id')->nullable();
            $table->foreignId('escort_id')->nullable();
            $table->float('tariff', 10, 2)->nullable();
            $table->float('tax', 10, 2)->nullable();
            $table->string('type')->nullable();
            $table->string('gender')->nullable();
            $table->string('seat')->nullable();
            $table->string('ticket')->nullable();
            $table->float('discount', 10, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passengers');
    }
};
