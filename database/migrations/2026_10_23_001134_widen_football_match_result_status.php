<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('football_matches', function (Blueprint $table) {
            $table->string('result_status', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('football_matches')->whereRaw('LENGTH(result_status) > ?', [20])->exists()) {
            throw new RuntimeException('Cannot shorten result_status while values longer than 20 characters exist.');
        }

        Schema::table('football_matches', function (Blueprint $table) {
            $table->string('result_status', 20)->nullable()->change();
        });
    }
};
