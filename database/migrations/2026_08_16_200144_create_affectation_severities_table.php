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
        Schema::create('affectation_severities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incident_type_id');
            $table->string('code', 50);
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('incident_type_id')->references('id')->on('incident_types')->cascadeOnDelete();
            $table->unique(['incident_type_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectation_severities');
    }
};
