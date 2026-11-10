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
        Schema::create('nameplates', function (Blueprint $table) {
            $table->id();
            $table->string('nameplate_code', 100)->unique();
            $table->string('assy_number', 50)->nullable()->index();
            $table->string('suffix', 10)->nullable();
            $table->string('date_code', 10)->nullable();
            $table->date('production_date')->nullable();
            $table->string('serial_number', 20)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('source', 20)->default('manual'); // 'import' | 'manual'
            $table->boolean('is_manual_edit')->default(false);
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nameplates');
    }
};

