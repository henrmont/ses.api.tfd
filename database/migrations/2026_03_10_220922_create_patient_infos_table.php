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
        Schema::create('patient_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id');
            $table->string('observation')->nullable();
            $table->integer('control_number')->nullable();
            $table->string('sigadoc')->nullable();
            $table->foreignId('file_sigadoc_id')->nullable();
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_infos');
    }
};
