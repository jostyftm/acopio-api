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
        Schema::create('sms_records', function (Blueprint $table) {
            $table->id();
            $table->string('origin_phone', 24);
            $table->text('body');
            $table->string('direction', 10)->default('incoming')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_records');
    }
};
