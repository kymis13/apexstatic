<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legends', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('name', 60);
            $table->string('real_name', 100);
            $table->enum('role', ['assault', 'skirmisher', 'recon', 'support', 'controller'])->index();
            $table->tinyInteger('difficulty')->default(2);
            $table->string('tagline', 180)->nullable();
            $table->text('lore')->nullable();
            $table->string('passive_name', 100)->nullable();
            $table->string('passive_desc', 500)->nullable();
            $table->string('tactical_name', 100)->nullable();
            $table->string('tactical_desc', 500)->nullable();
            $table->string('ultimate_name', 100)->nullable();
            $table->string('ultimate_desc', 500)->nullable();
            $table->string('forte', 180)->nullable();
            $table->string('tip', 500)->nullable();
            $table->string('image')->nullable();
            $table->string('accent_color', 7)->default('#ff4d3d');
            $table->decimal('pick_rate', 5, 2)->default(0);
            $table->string('release_season', 40)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legends');
    }
};
