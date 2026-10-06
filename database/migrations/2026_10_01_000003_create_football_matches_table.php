<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_matches', function (Blueprint $table) {
            $table->id();
            $table->string('unique_key', 512)->unique();
            $table->string('betfair_match_id')->nullable()->index();
            $table->string('sports_api_pro_match_id')->nullable()->index();
            $table->string('team1');
            $table->string('team2');
            $table->string('tournament')->nullable();
            $table->string('result_status', 20)->nullable()->index();
            $table->string('minutes_status', 20)->nullable();
            $table->unsignedBigInteger('start_timestamp')->default(0)->index();
            $table->boolean('data_sync_complete')->default(false)->index();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_matches');
    }
};
