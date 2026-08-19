<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('casualties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affectation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('type', 20);
            $table->foreignId('cause_id')->nullable()->constrained('casualty_causes')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
            $table->index('cause_id');
            $table->index('affectation_id');
        });

        DB::statement("ALTER TABLE casualties ADD CONSTRAINT casualties_type_check CHECK (type in ('deceased', 'injured'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casualties');
    }
};
