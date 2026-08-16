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
        Schema::table('needs', function (Blueprint $table) {
            $table->unsignedBigInteger('severity_need_id')->nullable(false)->change();
            $table->foreign('severity_need_id')
                ->references('id')
                ->on('severity_needs')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('needs', function (Blueprint $table) {
            $table->dropForeign(['severity_need_id']);
            $table->dropColumn('severity_need_id');
        });
    }
};
