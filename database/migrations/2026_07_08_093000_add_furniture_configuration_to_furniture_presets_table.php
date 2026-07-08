<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('furniture_presets', function (Blueprint $table) {
            $table->decimal('opening_height_m', 8, 2)->default(2.50)->after('description');
            $table->decimal('opening_width_m', 8, 2)->default(2.00)->after('opening_height_m');
            $table->decimal('opening_depth_cm', 8, 2)->default(60.00)->after('opening_width_m');
            $table->string('furniture_type')->nullable()->after('opening_depth_cm');
            $table->string('side_type')->nullable()->after('furniture_type');
            $table->json('configuration')->nullable()->after('side_type');
        });
    }

    public function down(): void
    {
        Schema::table('furniture_presets', function (Blueprint $table) {
            $table->dropColumn([
                'opening_height_m',
                'opening_width_m',
                'opening_depth_cm',
                'furniture_type',
                'side_type',
                'configuration',
            ]);
        });
    }
};
