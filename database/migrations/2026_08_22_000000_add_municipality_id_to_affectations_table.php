<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affectations', function (Blueprint $table): void {
            $table->foreignId('municipality_id')->nullable()->constrained()->nullOnDelete()->index();
        });

        DB::statement('
            UPDATE affectations
            SET municipality_id = m.id
            FROM municipalities m
            WHERE ST_Covers(m.boundary, affectations.location)
              AND affectations.location IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table): void {
            $table->dropForeign(['municipality_id']);
            $table->dropColumn('municipality_id');
        });
    }
};
