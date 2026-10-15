<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baseball_markets', function (Blueprint $table) {
            $table->id();
            $table->string('selection_key', 64)->unique();
            $table->string('baseball_match_unique_key', 512)->index('baseball_markets_match_key_index');
            $table->string('market_id')->nullable()->index();
            $table->string('market_type', 100)->nullable()->index();
            $table->string('runner_id')->nullable()->index();
            $table->string('runner')->nullable()->index();
            $table->unsignedInteger('sort_priority')->nullable();
            $table->decimal('handicap', 10, 4)->nullable();
            $table->decimal('previous_back_odds', 12, 4)->default(0);
            $table->decimal('current_back_odds', 12, 4)->default(0)->index();
            $table->decimal('back_size', 16, 2)->default(0);
            $table->decimal('lay_odds', 12, 4)->default(0);
            $table->decimal('lay_size', 16, 2)->default(0);
            $table->json('payload');
            $table->timestamps();

            $table->index(
                ['baseball_match_unique_key', 'market_type'],
                'baseball_markets_match_type_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baseball_markets');
    }
};
