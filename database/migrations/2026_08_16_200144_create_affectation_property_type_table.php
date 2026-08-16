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
        Schema::create('affectation_property_type', function (Blueprint $table) {
            $table->unsignedBigInteger('affectation_id');
            $table->unsignedBigInteger('property_type_id');
            $table->timestamps();

            $table->foreign('affectation_id')->references('id')->on('affectations')->cascadeOnDelete();
            $table->foreign('property_type_id')->references('id')->on('property_types')->cascadeOnDelete();
            $table->primary(['affectation_id', 'property_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectation_property_type');
    }
};
