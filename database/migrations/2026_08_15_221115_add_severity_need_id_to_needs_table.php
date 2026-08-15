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
        Schema::table('needs', function (Blueprint $table) {
            $table->unsignedBigInteger('severity_need_id')->nullable()->after('name');
        });

        $levels = [
            ['code_level' => 'high', 'display_name' => 'Alto'],
            ['code_level' => 'medium', 'display_name' => 'Medio'],
            ['code_level' => 'low', 'display_name' => 'Bajo'],
        ];

        foreach ($levels as $level) {
            DB::table('severity_needs')->updateOrInsert(
                ['code_level' => $level['code_level']],
                ['display_name' => $level['display_name'], 'created_at' => now(), 'updated_at' => now()],
            );
        }

        $fallbackLevelId = DB::table('severity_needs')->where('code_level', 'medium')->value('id');

        foreach (DB::table('needs')->whereNull('severity_need_id')->pluck('id') as $needId) {
            DB::table('needs')->where('id', $needId)->update(['severity_need_id' => $fallbackLevelId]);
        }

        Schema::table('needs', function (Blueprint $table) {
            $table->unsignedBigInteger('severity_need_id')->nullable(false)->change();
            $table->foreign('severity_need_id')
                ->references('id')
                ->on('severity_needs')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('needs', function (Blueprint $table) {
            $table->dropForeign(['severity_need_id']);
            $table->dropColumn('severity_need_id');
            $table->string('severity', 20)->nullable()->after('name');
        });
    }
};
