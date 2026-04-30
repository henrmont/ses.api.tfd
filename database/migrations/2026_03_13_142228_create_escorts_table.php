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
        Schema::create('escorts', function (Blueprint $table) {
            $table->id();
            $table->string('cns')->unique();
            $table->foreignId('file_cns_id')->nullable();
            $table->string('document')->unique();
            $table->foreignId('file_document_id')->nullable();
            $table->string('name');
            $table->string('relation')->nullable();
            $table->timestamp('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->boolean('is_same_address')->default(false);
            $table->string('cep');
            $table->string('address');
            $table->foreignId('file_address_id')->nullable();
            $table->string('number');
            $table->string('complement')->nullable();
            $table->string('neighborhood');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escorts');
    }
};
