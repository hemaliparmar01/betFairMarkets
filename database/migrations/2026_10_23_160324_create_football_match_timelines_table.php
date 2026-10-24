<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_match_timelines', function (Blueprint $table): void {
            $table->id();
            $table->string('match_id')->unique();
            $table->json('timeline_data');
            $table->string('content_hash', 64);
            $table->unsignedBigInteger('version');
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_match_timelines');
    }
};
