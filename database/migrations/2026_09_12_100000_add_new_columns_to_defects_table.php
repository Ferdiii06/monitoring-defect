<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tambah kolom baru BERDAMPINGAN dengan kolom lama (kolom lama tidak dihapus).
     * Idempotent: skip jika kolom sudah ada.
     */
    public function up(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            if (!Schema::hasColumn('defects', 'inspect_quantity')) {
                $table->integer('inspect_quantity')->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('defects', 'ditemukan_oleh')) {
                $table->string('ditemukan_oleh')->nullable()->after('inspect_quantity');
            }
            if (!Schema::hasColumn('defects', 'pattern')) {
                $table->string('pattern')->nullable()->after('ditemukan_oleh');
            }
            if (!Schema::hasColumn('defects', 'carline_id')) {
                $table->unsignedBigInteger('carline_id')->nullable()->after('pattern');
                $table->foreign('carline_id')->references('id')->on('carlines')->nullOnDelete();
            }
            if (!Schema::hasColumn('defects', 'inspect_process_type_id')) {
                $table->unsignedBigInteger('inspect_process_type_id')->nullable()->after('carline_id');
                $table->foreign('inspect_process_type_id')->references('id')->on('inspect_process_types')->nullOnDelete();
            }
            if (!Schema::hasColumn('defects', 'defect_type_id')) {
                $table->unsignedBigInteger('defect_type_id')->nullable()->after('inspect_process_type_id');
                $table->foreign('defect_type_id')->references('id')->on('defect_types')->nullOnDelete();
            }
            if (!Schema::hasColumn('defects', 'sub_defect_type_id')) {
                $table->unsignedBigInteger('sub_defect_type_id')->nullable()->after('defect_type_id');
                $table->foreign('sub_defect_type_id')->references('id')->on('sub_defect_types')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->dropForeign(['carline_id']);
            $table->dropForeign(['inspect_process_type_id']);
            $table->dropForeign(['defect_type_id']);
            $table->dropForeign(['sub_defect_type_id']);

            $table->dropColumn([
                'inspect_quantity',
                'ditemukan_oleh',
                'pattern',
                'carline_id',
                'inspect_process_type_id',
                'defect_type_id',
                'sub_defect_type_id',
            ]);
        });
    }
};
