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
            $table->unsignedBigInteger('person_id')->nullable()->change();
            $table->dropUnique('affectations_person_id_unique');

            $table->unsignedBigInteger('incident_type_id')->nullable()->after('person_id');

            $table->foreign('incident_type_id')->references('id')->on('incident_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->dropForeign(['incident_type_id']);
            $table->dropColumn('incident_type_id');

            $table->unique('person_id');
            $table->unsignedBigInteger('person_id')->nullable(false)->change();
        });
    }
};
