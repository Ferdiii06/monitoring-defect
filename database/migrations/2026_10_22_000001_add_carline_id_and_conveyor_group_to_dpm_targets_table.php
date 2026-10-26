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
        Schema::table('dpm_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('dpm_targets', 'carline_id')) {
                $table->foreignId('carline_id')->nullable()->after('jenis_assy')->constrained('carlines')->restrictOnDelete();
            }
            if (!Schema::hasColumn('dpm_targets', 'conveyor_group')) {
                $table->string('conveyor_group', 50)->nullable()->after('carline_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dpm_targets', function (Blueprint $table) {
            if (Schema::hasColumn('dpm_targets', 'carline_id')) {
                $table->dropForeign(['carline_id']);
                $table->dropColumn('carline_id');
            }
            if (Schema::hasColumn('dpm_targets', 'conveyor_group')) {
                $table->dropColumn('conveyor_group');
            }
        });
    }
};
