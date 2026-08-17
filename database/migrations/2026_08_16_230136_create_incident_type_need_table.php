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
        Schema::create('incident_type_need', function (Blueprint $table) {
            $table->unsignedBigInteger('incident_type_id');
            $table->unsignedBigInteger('need_id');
            $table->timestamps();

            $table->foreign('incident_type_id')->references('id')->on('incident_types')->cascadeOnDelete();
            $table->foreign('need_id')->references('id')->on('needs')->cascadeOnDelete();
            $table->primary(['incident_type_id', 'need_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_type_need');
    }
};
