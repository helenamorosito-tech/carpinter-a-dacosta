<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ant_furniture_remnants', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->decimal('width_cm', 8, 2);
            $table->decimal('length_cm', 8, 2);
            $table->unsignedSmallInteger('thickness_mm');
            $table->string('color', 32);
            $table->string('status', 20)->default('available');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['status', 'is_active', 'thickness_mm']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ant_furniture_remnants');
    }
};
