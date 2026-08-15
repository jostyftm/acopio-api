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
        Schema::create('affectation_evidence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affectation_id');
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->foreign('affectation_id')->references('id')->on('affectations')->cascadeOnDelete();
            $table->index('affectation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectation_evidence');
    }
};
