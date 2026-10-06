<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediction_votes', function (Blueprint $table) {
            $table->id();
            $table->string('vote_key', 64)->unique();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->string('sport', 50)->index();
            $table->string('match_id')->index();
            $table->string('poll', 50);
            $table->string('value', 50);
            $table->timestamps();

            $table->index(['sport', 'match_id', 'poll', 'value'], 'prediction_votes_count_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_votes');
    }
};
