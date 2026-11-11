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
        Schema::table('nameplate_settings', function (Blueprint $table) {
            $table->boolean('assy_customer')->default(false)->after('carline');
            $table->string('assy_no', 100)->nullable()->after('date_format');
            $table->string('suffix', 100)->nullable()->after('assy_no');
            $table->string('level', 100)->nullable()->after('suffix');
            $table->string('date_field', 100)->nullable()->after('level');
            $table->string('sequence_label', 100)->nullable()->after('date_field');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nameplate_settings', function (Blueprint $table) {
            $table->dropColumn([
                'assy_customer',
                'assy_no',
                'suffix',
                'level',
                'date_field',
                'sequence_label',
            ]);
        });
    }
};

