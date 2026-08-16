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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('modules')->cascadeOnDelete();
            $table->string('key')->unique();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->string('path')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('display_sidebar')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
