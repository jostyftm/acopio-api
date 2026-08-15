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
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id');
            $table->string('severity', 20);
            $table->text('description')->nullable();
            $table->geometry('location', 'POINT', 4326)->nullable();
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('people')->cascadeOnDelete();
            $table->unique('person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
