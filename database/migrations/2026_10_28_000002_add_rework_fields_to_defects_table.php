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
            if (!Schema::hasColumn('defects', 'waktu_in')) {
                $table->dateTime('waktu_in')->nullable();
            }
            if (!Schema::hasColumn('defects', 'waktu_out')) {
                $table->dateTime('waktu_out')->nullable();
            }
            if (!Schema::hasColumn('defects', 'durasi_rework_menit')) {
                $table->unsignedInteger('durasi_rework_menit')->nullable();
            }
            if (!Schema::hasColumn('defects', 'status_rework')) {
                $table->string('status_rework', 10)->nullable()->index();
            }
            if (!Schema::hasColumn('defects', 'kanban_assy')) {
                $table->string('kanban_assy')->nullable();
            }
            if (!Schema::hasColumn('defects', 'nameplate_lama')) {
                $table->string('nameplate_lama')->nullable();
            }
            if (!Schema::hasColumn('defects', 'nameplate_baru')) {
                $table->string('nameplate_baru')->nullable();
            }
            if (!Schema::hasColumn('defects', 'no_defective_tag')) {
                $table->string('no_defective_tag')->nullable();
            }
            if (!Schema::hasColumn('defects', 'status_4m')) {
                $table->string('status_4m')->nullable();
            }
            if (!Schema::hasColumn('defects', 'posisi_detail_rework')) {
                $table->text('posisi_detail_rework')->nullable();
            }
            if (!Schema::hasColumn('defects', 'fc_h')) {
                $table->string('fc_h')->nullable();
            }
            if (!Schema::hasColumn('defects', 'fc_w')) {
                $table->string('fc_w')->nullable();
            }
            if (!Schema::hasColumn('defects', 'rc_h')) {
                $table->string('rc_h')->nullable();
            }
            if (!Schema::hasColumn('defects', 'rc_w')) {
                $table->string('rc_w')->nullable();
            }
            if (!Schema::hasColumn('defects', 'no_box')) {
                $table->string('no_box')->nullable();
            }
            if (!Schema::hasColumn('defects', 'ujung')) {
                $table->string('ujung')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $columnsToDrop = [];
            $columns = [
                'waktu_in',
                'waktu_out',
                'durasi_rework_menit',
                'status_rework',
                'kanban_assy',
                'nameplate_lama',
                'nameplate_baru',
                'no_defective_tag',
                'status_4m',
                'posisi_detail_rework',
                'fc_h',
                'fc_w',
                'rc_h',
                'rc_w',
                'no_box',
                'ujung',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('defects', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
