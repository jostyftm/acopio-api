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
        Schema::create('search_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('searched_name', 240);
            $table->string('document_type', 10)->nullable();
            $table->string('document_number', 32)->nullable();
            $table->string('municipality', 120)->nullable();
            $table->string('reporter_name', 240);
            $table->string('reporter_phone', 24)->index();
            $table->string('relationship', 120)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('located_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_reports');
    }
};
