<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('furniture_presets', function (Blueprint $table) {
            $table->unsignedTinyInteger('structure_board_thickness_mm')
                ->default(16)
                ->after('selected_boards');
        });
    }

    public function down(): void
    {
        Schema::table('furniture_presets', function (Blueprint $table) {
            $table->dropColumn('structure_board_thickness_mm');
        });
    }
};
