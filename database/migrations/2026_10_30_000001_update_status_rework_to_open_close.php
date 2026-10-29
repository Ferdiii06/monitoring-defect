<?php

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
        if (Schema::hasTable('defects') && Schema::hasColumn('defects', 'status_rework')) {
            DB::table('defects')
                ->where('status_rework', 'Berjalan')
                ->update(['status_rework' => 'OPEN']);

            DB::table('defects')
                ->where('status_rework', 'Selesai')
                ->update(['status_rework' => 'CLOSE']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('defects') && Schema::hasColumn('defects', 'status_rework')) {
            DB::table('defects')
                ->where('status_rework', 'OPEN')
                ->update(['status_rework' => 'Berjalan']);

            DB::table('defects')
                ->where('status_rework', 'CLOSE')
                ->update(['status_rework' => 'Selesai']);
        }
    }
};
