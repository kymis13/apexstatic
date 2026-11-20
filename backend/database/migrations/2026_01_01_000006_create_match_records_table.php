<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legend_id')->nullable()->constrained()->nullOnDelete();
            $table->string('map', 60);
            $table->string('mode', 40);
            $table->unsignedTinyInteger('placement');
            $table->unsignedTinyInteger('kills')->default(0);
            $table->unsignedInteger('damage')->default(0);
            $table->smallInteger('rp')->default(0);
            $table->timestamp('played_at')->index();
            $table->timestamps();

            $table->index(['player_profile_id', 'played_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_records');
    }
};
