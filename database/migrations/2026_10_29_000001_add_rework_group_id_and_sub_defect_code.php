<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Support\SubDefectHelper;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            if (!Schema::hasColumn('defects', 'rework_group_id')) {
                $table->string('rework_group_id', 36)->nullable()->index();
            }
        });

        Schema::table('sub_defect_types', function (Blueprint $table) {
            if (!Schema::hasColumn('sub_defect_types', 'code')) {
                $table->string('code', 10)->nullable()->index();
            }
        });

        // Backfill sub defect codes using DB::table and SubDefectHelper
        $rows = DB::table('sub_defect_types')->select('id', 'name')->get();
        foreach ($rows as $row) {
            $derived = SubDefectHelper::deriveCode($row->name);
            if ($derived !== null) {
                DB::table('sub_defect_types')
                    ->where('id', $row->id)
                    ->update(['code' => $derived]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            if (Schema::hasColumn('defects', 'rework_group_id')) {
                $table->dropColumn('rework_group_id');
            }
        });

        Schema::table('sub_defect_types', function (Blueprint $table) {
            if (Schema::hasColumn('sub_defect_types', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
