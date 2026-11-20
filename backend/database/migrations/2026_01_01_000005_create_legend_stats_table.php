<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legend_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legend_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('matches')->default(0);
            $table->unsignedInteger('kills')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedBigInteger('damage')->default(0);
            $table->unsignedInteger('playtime_hours')->default(0);
            $table->string('best_season', 40)->nullable();
            $table->decimal('winrate', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['player_profile_id', 'legend_id']);
            $table->index(['player_profile_id', 'kills']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legend_stats');
    }
};
