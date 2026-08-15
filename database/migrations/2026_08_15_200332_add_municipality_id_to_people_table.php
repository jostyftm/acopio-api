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
            $table->unsignedBigInteger('municipality_id')->nullable()->after('municipality');
        });

        DB::statement(
            'UPDATE people SET municipality_id = municipalities.id '
                .'FROM municipalities WHERE municipalities.code = people.mpio_code;'
        );

        Schema::table('people', function (Blueprint $table) {
            $table->foreign('municipality_id')->references('id')->on('municipalities')->nullOnDelete();
            $table->index('municipality_id');
            $table->dropColumn(['department', 'mpio_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('department', 120)->nullable()->after('municipality');
            $table->char('mpio_code', 5)->nullable()->after('department');
            $table->index('mpio_code');
        });

        DB::statement(
            'UPDATE people SET mpio_code = municipalities.code, department = departments.name '
                .'FROM municipalities '
                .'JOIN departments ON departments.id = municipalities.department_id '
                .'WHERE municipalities.id = people.municipality_id;'
        );

        Schema::table('people', function (Blueprint $table) {
            $table->dropForeign(['municipality_id']);
            $table->dropIndex(['municipality_id']);
            $table->dropColumn('municipality_id');
        });
    }
};
