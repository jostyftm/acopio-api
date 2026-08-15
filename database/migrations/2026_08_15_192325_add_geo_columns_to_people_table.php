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
        Schema::table('people', function (Blueprint $table) {
            $table->string('department', 120)->nullable()->after('municipality');
            $table->char('mpio_code', 5)->nullable()->after('department');
            $table->index('mpio_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropIndex(['mpio_code']);
            $table->dropColumn(['department', 'mpio_code']);
        });
    }
};
