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
        Schema::create('patient_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id');
            $table->foreignId('medical_professional_id')->nullable();
            $table->foreignId('social_professional_id')->nullable();
            $table->foreignId('travel_professional_id')->nullable();
            $table->foreignId('cost_assistance_professional_id')->nullable();
            $table->foreignId('accountability_professional_id')->nullable();
            $table->foreignId('owner_professional_id');
            $table->foreignId('hospital_unity_id')->nullable();
            $table->string('type');
            $table->timestamp('consultation_date')->nullable();
            $table->text('observation');
            $table->string('back_to_owner')->nullable();
            $table->string('back_to_medical')->nullable();
            $table->string('back_to_social')->nullable();
            $table->string('back_to_travel')->nullable();
            $table->string('back_to_cost_assistance')->nullable();
            $table->foreignId('back_from_travel')->nullable();
            $table->foreignId('back_from_cost_assistance')->nullable();
            $table->boolean('is_owner_bookmark')->default(false);
            $table->boolean('is_medical_bookmark')->default(false);
            $table->boolean('is_social_bookmark')->default(false);
            $table->boolean('is_travel_bookmark')->default(false);
            $table->boolean('is_cost_assistance_bookmark')->default(false);
            $table->boolean('is_accountability_bookmark')->default(false);
            $table->boolean('is_opinion_archived')->default(false);
            $table->boolean('is_travel_archived')->default(false);
            $table->boolean('is_cost_assistance_archived')->default(false);
            $table->boolean('is_accountability_archived')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_requests');
    }
};
