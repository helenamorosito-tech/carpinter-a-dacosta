<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('furniture_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('furniture_preset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('reference')->nullable();
            $table->decimal('height_cm', 8, 2);
            $table->decimal('width_cm', 8, 2);
            $table->decimal('depth_cm', 8, 2);
            $table->string('placement_type')->default('costado_visto');
            $table->string('door_type')->default('abatibles');
            $table->unsignedTinyInteger('shelf_count')->default(0);
            $table->decimal('shelf_thickness_cm', 8, 2)->default(1.60);
            $table->unsignedTinyInteger('module_count')->default(1);
            $table->unsignedTinyInteger('door_count')->nullable();
            $table->decimal('effective_width_cm', 8, 2)->nullable();
            $table->json('pieces')->nullable();
            $table->json('cut_plan')->nullable();
            $table->longText('svg_diagram')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('furniture_projects');
    }
};
