<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update the dashboard module path after the route rename.
     */
    public function up(): void
    {
        DB::table('modules')
            ->where('key', 'dashboard')
            ->where('path', '/dashboard2')
            ->update(['path' => '/dashboard']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('modules')
            ->where('key', 'dashboard')
            ->where('path', '/dashboard')
            ->update(['path' => '/dashboard2']);
    }
};
