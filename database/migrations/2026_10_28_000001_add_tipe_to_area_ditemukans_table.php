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
        Schema::table('area_ditemukans', function (Blueprint $table) {
            if (!Schema::hasColumn('area_ditemukans', 'tipe')) {
                $table->string('tipe', 20)->default('internal_defect');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('area_ditemukans', function (Blueprint $table) {
            if (Schema::hasColumn('area_ditemukans', 'tipe')) {
                $table->dropColumn('tipe');
            }
        });
    }
};
