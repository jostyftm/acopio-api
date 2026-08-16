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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_type_id')->constrained()->restrictOnDelete();
            $table->string('name', 240);
            $table->string('nit', 40)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('contact_name', 200)->nullable();
            $table->string('contact_email', 240)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
