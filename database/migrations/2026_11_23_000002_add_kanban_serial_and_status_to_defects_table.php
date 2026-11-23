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
            if (!Schema::hasColumn('defects', 'kanban_serial')) {
                $table->unsignedInteger('kanban_serial')->nullable();
            }
            if (!Schema::hasColumn('defects', 'kanban_status')) {
                $table->string('kanban_status', 30)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('defects', 'kanban_serial')) {
                $drop[] = 'kanban_serial';
            }
            if (Schema::hasColumn('defects', 'kanban_status')) {
                $drop[] = 'kanban_status';
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
