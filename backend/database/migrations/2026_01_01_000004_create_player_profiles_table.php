<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('steam_id', 17)->nullable()->index();
            $table->unsignedInteger('level')->default(1);
            $table->string('rank_tier', 40)->default('Бронза IV');
            $table->unsignedInteger('rank_points')->default(0)->index();
            $table->unsignedInteger('hours_played')->default(0);
            $table->unsignedInteger('total_kills')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedBigInteger('total_damage')->default(0);
            $table->unsignedInteger('matches_played')->default(0);
            $table->decimal('kd', 5, 2)->default(0);
            $table->decimal('winrate', 5, 2)->default(0);
            $table->decimal('headshot_rate', 5, 2)->default(0);
            $table->unsignedTinyInteger('battle_pass')->default(0);
            $table->foreignId('main_legend_id')->nullable()->constrained('legends')->nullOnDelete();
            $table->char('country', 2)->default('RU');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};
