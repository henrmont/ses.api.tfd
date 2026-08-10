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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_request_id');
            $table->foreignId('cost_assistance_id');
            $table->foreignId('travel_id');
            $table->foreignId('payment_professional_id');
            $table->string('sigadoc')->nullable();
            $table->string('creditor')->nullable();
            $table->string('document_number')->nullable();
            $table->boolean('is_payment_bookmark')->default(false);
            $table->boolean('is_payment_archived')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
