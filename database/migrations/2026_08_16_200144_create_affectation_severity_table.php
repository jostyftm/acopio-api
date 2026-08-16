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
        Schema::create('affectation_severity', function (Blueprint $table) {
            $table->unsignedBigInteger('affectation_id');
            $table->unsignedBigInteger('affectation_severity_id');
            $table->timestamps();

            $table->foreign('affectation_id')->references('id')->on('affectations')->cascadeOnDelete();
            $table->foreign('affectation_severity_id')->references('id')->on('affectation_severities')->cascadeOnDelete();
            $table->primary(['affectation_id', 'affectation_severity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectation_severity');
    }
};
