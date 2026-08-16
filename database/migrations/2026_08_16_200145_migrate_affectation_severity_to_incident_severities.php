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
        $incidentTypeId = DB::table('incident_types')->insertGetId([
            'code' => 'derrumbes',
            'display_name' => 'Derrumbes',
            'description' => 'Deslizamientos o colapso de terrenos que afectan viviendas e infraestructura.',
            'severity_mode' => 'single',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $severityIds = [];
        foreach (['partial' => 'Afectación parcial', 'total' => 'Afectación total'] as $code => $displayName) {
            $severityIds[$code] = DB::table('affectation_severities')->insertGetId([
                'incident_type_id' => $incidentTypeId,
                'code' => $code,
                'display_name' => $displayName,
                'description' => null,
                'order' => $code === 'partial' ? 1 : 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $severityByCode = [
            'partial' => $severityIds['partial'],
            'total' => $severityIds['total'],
        ];

        DB::table('affectations')->orderBy('id')->chunkById(100, function ($affectations) use ($incidentTypeId, $severityByCode): void {
            foreach ($affectations as $affectation) {
                if ($affectation->incident_type_id === null) {
                    DB::table('affectations')
                        ->where('id', $affectation->id)
                        ->update(['incident_type_id' => $incidentTypeId]);
                }

                $severityId = $severityByCode[$affectation->severity] ?? null;

                if ($severityId !== null) {
                    DB::table('affectation_severity')->insert([
                        'affectation_id' => $affectation->id,
                        'affectation_severity_id' => $severityId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        Schema::table('affectations', function (Blueprint $table) {
            $table->dropColumn('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->string('severity', 20)->nullable()->after('incident_type_id');
        });

        DB::table('affectation_severity')->delete();

        $severityRows = DB::table('affectation_severities')->whereIn('code', ['partial', 'total'])->get();

        foreach ($severityRows as $row) {
            DB::table('affectations')
                ->where('incident_type_id', $row->incident_type_id)
                ->update(['severity' => $row->code]);
        }

        DB::table('affectation_severities')->whereIn('code', ['partial', 'total'])->delete();
        DB::table('incident_types')->where('code', 'derrumbes')->delete();
    }
};
