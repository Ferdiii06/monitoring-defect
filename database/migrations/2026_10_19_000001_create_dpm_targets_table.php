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
        Schema::create('dpm_targets', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_assy', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('target_dpm', 14, 2);
            $table->timestamps();

            $table->index(['jenis_assy', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dpm_targets');
    }
};
