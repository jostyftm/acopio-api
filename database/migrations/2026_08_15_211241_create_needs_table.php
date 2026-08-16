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
        Schema::create('needs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('normalized_name', 120);
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('severity_need_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('name');
            $table->unique('normalized_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('needs');
    }
};
