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
        Schema::create('travels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_request_id');
            $table->string('transportation');
            $table->string('type')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->timestamp('departure_date')->nullable();
            $table->timestamp('return_date')->nullable();
            $table->text('description')->nullable();
            $table->string('os')->nullable();
            $table->string('locator')->nullable();
            $table->string('company')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travels');
    }
};
