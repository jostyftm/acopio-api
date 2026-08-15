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
            $table->geometry('location', 'POINT', 4326)->nullable();
        });

        DB::statement(
            'UPDATE people SET location = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326) '
                .'WHERE latitude IS NOT NULL AND longitude IS NOT NULL;'
        );

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('people', function (Blueprint $table) {
            $table->spatialIndex('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropSpatialIndex(['location']);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });

        DB::statement(
            'UPDATE people SET latitude = ST_Y(location), longitude = ST_X(location) WHERE location IS NOT NULL;'
        );

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
