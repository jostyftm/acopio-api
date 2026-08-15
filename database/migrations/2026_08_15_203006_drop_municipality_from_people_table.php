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
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('municipality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('municipality', 120)->nullable()->index();
        });

        DB::statement(
            <<<'SQL'
            UPDATE people p
            SET municipality = initcap(lower(m.name))
            FROM municipalities m
            WHERE m.id = p.municipality_id
            SQL
        );
    }
};
