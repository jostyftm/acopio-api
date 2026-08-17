<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coverage_zones', function (Blueprint $table) {
            $table->json('map_center')->nullable()->after('polygon');
            $table->decimal('map_zoom', 5, 2)->nullable()->after('map_center');
        });
    }

    public function down(): void
    {
        Schema::table('coverage_zones', function (Blueprint $table) {
            $table->dropColumn(['map_center', 'map_zoom']);
        });
    }
};
