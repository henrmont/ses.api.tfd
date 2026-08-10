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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_care_id');
            $table->string('protocol');
            $table->foreignId('cid_id');
            $table->string('specialty')->nullable();
            $table->boolean('lawsuit')->default(false);
            $table->text('diagnosis');
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_export')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
