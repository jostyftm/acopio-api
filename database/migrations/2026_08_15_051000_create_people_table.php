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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 10)->index();
            $table->string('document_number', 32)->index();
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->string('phone', 24)->index();
            $table->string('municipality', 120)->index();
            $table->string('neighborhood', 120)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 20)->default('registered')->index();
            $table->json('special_needs')->nullable();
            $table->string('source', 10)->default('web')->index();
            $table->boolean('data_consent')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('located_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
