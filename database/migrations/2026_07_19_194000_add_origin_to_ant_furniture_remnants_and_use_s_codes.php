<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ant_furniture_remnants', function (Blueprint $table) {
            $table->string('origin', 32)->default('registered')->after('status');
        });

        // Unificar IDs a S-XX (antes P-XX).
        $rows = DB::table('ant_furniture_remnants')->orderBy('id')->get(['id', 'code']);
        $n = 1;
        foreach ($rows as $row) {
            DB::table('ant_furniture_remnants')
                ->where('id', $row->id)
                ->update([
                    'code' => sprintf('S-%02d', $n),
                    'origin' => 'registered',
                ]);
            $n++;
        }
    }

    public function down(): void
    {
        Schema::table('ant_furniture_remnants', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};
