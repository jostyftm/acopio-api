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
        Schema::create('affectation_need', function (Blueprint $table) {
            $table->unsignedBigInteger('affectation_id');
            $table->unsignedBigInteger('need_id');
            $table->timestamps();

            $table->foreign('affectation_id')->references('id')->on('affectations')->cascadeOnDelete();
            $table->foreign('need_id')->references('id')->on('needs')->cascadeOnDelete();
            $table->primary(['affectation_id', 'need_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectation_need');
    }
};
