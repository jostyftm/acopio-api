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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_type_id')->constrained()->restrictOnDelete();
            $table->string('name', 240);
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address', 255)->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('available')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('operational');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
