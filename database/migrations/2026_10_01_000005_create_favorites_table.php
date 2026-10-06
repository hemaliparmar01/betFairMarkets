<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->string('favorite_key', 64)->unique();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->string('sport', 50);
            $table->string('event_key', 512);
            $table->string('event_id')->nullable();
            $table->string('team1')->nullable();
            $table->string('team2')->nullable();
            $table->unsignedBigInteger('start_timestamp')->nullable();
            $table->timestamps();

            $table->index(['sport', 'user_id'], 'favorites_sport_user_index');
            $table->index(['sport', 'session_id'], 'favorites_sport_session_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
