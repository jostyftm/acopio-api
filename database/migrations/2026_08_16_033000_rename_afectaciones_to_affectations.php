<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename the affectations module and its permissions to their English names.
     */
    public function up(): void
    {
        DB::table('modules')
            ->where('key', 'afectaciones')
            ->update(['key' => 'affectations']);

        DB::table('permissions')
            ->where('name', 'like', 'afectaciones.%')
            ->update([
                'name' => DB::raw("CONCAT('affectations', SUBSTRING(name, STRPOS(name, '.')))"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('modules')
            ->where('key', 'affectations')
            ->update(['key' => 'afectaciones']);

        DB::table('permissions')
            ->where('name', 'like', 'affectations.%')
            ->update([
                'name' => DB::raw("CONCAT('afectaciones', SUBSTRING(name, STRPOS(name, '.')))"),
            ]);
    }
};
