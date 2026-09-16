<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('theme')->default('sunrise');
            $table->json('skill_tags');
            $table->unsignedInteger('duration_seconds')->default(180);
            $table->unsignedTinyInteger('min_grade')->nullable();
            $table->unsignedTinyInteger('max_grade')->nullable();
            $table->string('status')->default('published');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_definition_id')->constrained();
            $table->foreignId('profile_id')->constrained();
            $table->string('status')->default('in_progress');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('metrics')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['profile_id', 'status']);
            $table->index(['game_definition_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('game_definitions');
    }
};
