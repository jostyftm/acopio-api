<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('organization_id')->constrained('affectation_statuses')->nullOnDelete();
            $table->string('address', 255)->nullable()->after('description');
            $table->foreignId('verified_by')->nullable()->after('location')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->timestamp('located_at')->nullable()->after('verified_at');
            $table->index('status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->dropIndex(['status_id']);
            $table->dropConstrainedForeignId('status_id');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['address', 'verified_at', 'located_at']);
        });
    }
};
