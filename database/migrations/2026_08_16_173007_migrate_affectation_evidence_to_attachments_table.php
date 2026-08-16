<?php

use App\Models\Affectation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columns = ['attachable_type', 'attachable_id', 'file_path', 'original_name', 'mime', 'size', 'created_at', 'updated_at'];

        $source = DB::table('affectation_evidence')
            ->selectRaw('? as attachable_type', [Affectation::class])
            ->selectRaw('affectation_id as attachable_id')
            ->selectRaw('file_path')
            ->selectRaw('original_name')
            ->selectRaw('mime')
            ->selectRaw('size')
            ->selectRaw('created_at')
            ->selectRaw('updated_at');

        DB::table('attachments')->insertUsing($columns, $source);

        Schema::dropIfExists('affectation_evidence');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('affectation_evidence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affectation_id');
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->foreign('affectation_id')->references('id')->on('affectations')->cascadeOnDelete();
            $table->index('affectation_id');
        });

        DB::table('affectation_evidence')->insertUsing(
            ['affectation_id', 'file_path', 'original_name', 'mime', 'size', 'created_at', 'updated_at'],
            DB::table('attachments')
                ->where('attachable_type', Affectation::class)
                ->select([
                    'attachments.attachable_id',
                    'attachments.file_path',
                    'attachments.original_name',
                    'attachments.mime',
                    'attachments.size',
                    'attachments.created_at',
                    'attachments.updated_at',
                ]),
        );
    }
};
