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
        Schema::table('defects', function (Blueprint $table) {
            if (!Schema::hasColumn('defects', 'is_susulan')) {
                $table->boolean('is_susulan')->default(false)->after('rework_group_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            if (Schema::hasColumn('defects', 'is_susulan')) {
                $table->dropColumn('is_susulan');
            }
        });
    }
};
