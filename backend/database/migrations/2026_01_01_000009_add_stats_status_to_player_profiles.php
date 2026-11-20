<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->enum('stats_status', ['pending', 'ok', 'not_found', 'error'])
                ->default('pending')
                ->after('battle_pass');
            $table->string('stats_error', 255)->nullable()->after('stats_status');
        });
    }

    public function down(): void
    {
        Schema::table('player_profiles', function (Blueprint $table) {
            $table->dropColumn(['stats_status', 'stats_error']);
        });
    }
};
