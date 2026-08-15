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
        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->char('dpto_code', 2);
            $table->string('dpto_name', 120);
            $table->char('code', 5)->unique();
            $table->string('name', 120);
            $table->string('normalized_name', 120);
            $table->geometry('centroid', 'POINT', 4326)->nullable();
            $table->geometry('boundary', 'MULTIPOLYGON', 4326);
            $table->timestamps();
        });

        DB::statement('CREATE INDEX municipalities_boundary_gist ON municipalities USING gist (boundary);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipalities');
    }
};
