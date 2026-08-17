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
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedBigInteger('municipality_id')->nullable()->after('organization_type_id');
            $table->foreign('municipality_id')->references('id')->on('municipalities')->nullOnDelete();
            $table->index('municipality_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['municipality_id']);
            $table->dropIndex(['municipality_id']);
            $table->dropColumn('municipality_id');
        });
    }
};
