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
            if (!Schema::hasColumn('defects', 'final_inspect_type_id')) {
                $table->unsignedBigInteger('final_inspect_type_id')->nullable()->after('inspect_process_type_id');
                $table->foreign('final_inspect_type_id')->references('id')->on('final_assy_inspect_types')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->dropForeign(['final_inspect_type_id']);
            $table->dropColumn('final_inspect_type_id');
        });
    }
};
