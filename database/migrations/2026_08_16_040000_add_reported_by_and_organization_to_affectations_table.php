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
        Schema::table('affectations', function (Blueprint $table) {
            $table->unsignedBigInteger('reported_by')->nullable()->after('person_id');
            $table->unsignedBigInteger('organization_id')->nullable()->after('reported_by');

            $table->foreign('reported_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();

            $table->index('reported_by');
            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->dropForeign(['reported_by']);
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['reported_by', 'organization_id']);
        });
    }
};
