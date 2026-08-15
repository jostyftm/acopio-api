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
        Schema::table('municipalities', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('code');
        });

        DB::statement(
            'INSERT INTO departments (code, name, normalized_name, created_at, updated_at) '
                .'SELECT DISTINCT dpto_code, dpto_name, UPPER(dpto_name), NOW(), NOW() FROM municipalities;'
        );

        DB::statement(
            'UPDATE municipalities SET department_id = departments.id '
                .'FROM departments WHERE departments.code = municipalities.dpto_code;'
        );

        Schema::table('municipalities', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable(false)->change();
            $table->foreign('department_id')->references('id')->on('departments');
            $table->index('department_id');
            $table->dropColumn(['dpto_code', 'dpto_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('municipalities', function (Blueprint $table) {
            $table->char('dpto_code', 2)->nullable();
            $table->string('dpto_name', 120)->nullable();
        });

        DB::statement(
            'UPDATE municipalities SET dpto_code = departments.code, dpto_name = departments.name '
                .'FROM departments WHERE departments.id = municipalities.department_id;'
        );

        Schema::table('municipalities', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropIndex(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
