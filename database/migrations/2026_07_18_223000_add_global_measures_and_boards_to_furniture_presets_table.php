<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('furniture_presets', function (Blueprint $table) {
            $table->decimal('global_height_cm', 8, 2)->nullable()->after('opening_depth_cm');
            $table->decimal('global_width_cm', 8, 2)->nullable()->after('global_height_cm');
            $table->decimal('global_depth_cm', 8, 2)->nullable()->after('global_width_cm');
            $table->decimal('board_width_cm', 8, 2)->default(122)->after('configuration');
            $table->decimal('board_height_cm', 8, 2)->default(244)->after('board_width_cm');
            $table->json('selected_boards')->nullable()->after('board_height_cm');
            $table->unsignedTinyInteger('back_board_thickness_mm')->default(10)->after('selected_boards');
            $table->unsignedSmallInteger('back_board_quantity')->default(1)->after('back_board_thickness_mm');
            $table->unsignedSmallInteger('calculated_boards_count')->nullable()->after('back_board_quantity');
            $table->json('pieces')->nullable()->after('calculated_boards_count');
            $table->json('cut_plan')->nullable()->after('pieces');
            $table->longText('svg_diagram')->nullable()->after('cut_plan');
        });
    }

    public function down(): void
    {
        Schema::table('furniture_presets', function (Blueprint $table) {
            $table->dropColumn([
                'global_height_cm',
                'global_width_cm',
                'global_depth_cm',
                'board_width_cm',
                'board_height_cm',
                'selected_boards',
                'back_board_thickness_mm',
                'back_board_quantity',
                'calculated_boards_count',
                'pieces',
                'cut_plan',
                'svg_diagram',
            ]);
        });
    }
};
