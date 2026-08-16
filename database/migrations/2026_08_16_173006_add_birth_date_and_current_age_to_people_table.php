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
            $table->date('birth_date')->nullable()->after('last_name');
            $table->unsignedInteger('current_age')->nullable()->after('birth_date');
            $table->string('phone', 24)->nullable()->change();
        });

        DB::statement(
            <<<'SQL'
            CREATE OR REPLACE FUNCTION people_set_current_age() RETURNS trigger AS $$
            BEGIN
                NEW.current_age := CASE
                    WHEN NEW.birth_date IS NULL THEN NULL
                    ELSE date_part('year', age(NEW.birth_date))::int
                END;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            SQL
        );

        DB::statement(
            'CREATE TRIGGER people_set_current_age_trigger '
            .'BEFORE INSERT OR UPDATE OF birth_date ON people '
            .'FOR EACH ROW EXECUTE FUNCTION people_set_current_age();'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS people_set_current_age_trigger ON people;');
        DB::statement('DROP FUNCTION IF EXISTS people_set_current_age();');

        Schema::table('people', function (Blueprint $table) {
            $table->string('phone', 24)->nullable(false)->change();
            $table->dropColumn(['current_age', 'birth_date']);
        });
    }
};
