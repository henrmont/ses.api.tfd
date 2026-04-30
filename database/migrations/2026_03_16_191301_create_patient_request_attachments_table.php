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
        Schema::create('patient_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_request_id');
            $table->foreignId('archive_id')->nullable();
            $table->string('name');
            $table->boolean('to_payment')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_request_attachments');
    }
};
