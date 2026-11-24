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
        Schema::table('carlines', function (Blueprint $table) {
            $table->string('jenis_assy', 20)->default('Final Assy')->after('name')->index();
        });

        // Backfill: car_type_id IS NULL menjadi 'Pre Assy', NOT NULL menjadi 'Final Assy'
        DB::table('carlines')->whereNull('car_type_id')->update(['jenis_assy' => 'Pre Assy']);
        DB::table('carlines')->whereNotNull('car_type_id')->update(['jenis_assy' => 'Final Assy']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carlines', function (Blueprint $table) {
            $table->dropColumn('jenis_assy');
        });
    }
};
