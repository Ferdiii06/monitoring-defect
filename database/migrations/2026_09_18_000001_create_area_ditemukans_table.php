<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('area_ditemukans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();
        $defaultAreas = ['CK', 'V1', 'V2', 'FC', 'WP', 'RI'];
        $data = array_map(function ($name) use ($now) {
            return [
                'name'       => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $defaultAreas);

        DB::table('area_ditemukans')->insert($data);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_ditemukans');
    }
};
