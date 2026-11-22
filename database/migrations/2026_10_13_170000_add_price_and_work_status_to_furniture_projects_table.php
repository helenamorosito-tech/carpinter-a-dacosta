<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('furniture_projects', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->after('status');
        });

        DB::table('furniture_projects')->where('status', 'draft')->update(['status' => 'en_proyecto']);
        DB::table('furniture_projects')->where('status', 'calculated')->update(['status' => 'terminado']);
        DB::table('furniture_projects')->whereNotIn('status', ['en_proyecto', 'en_obra', 'terminado'])
            ->update(['status' => 'en_proyecto']);
    }

    public function down(): void
    {
        DB::table('furniture_projects')->where('status', 'en_proyecto')->update(['status' => 'draft']);
        DB::table('furniture_projects')->where('status', 'terminado')->update(['status' => 'calculated']);
        DB::table('furniture_projects')->where('status', 'en_obra')->update(['status' => 'draft']);

        Schema::table('furniture_projects', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
