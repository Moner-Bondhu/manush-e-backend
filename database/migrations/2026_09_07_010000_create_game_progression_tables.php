<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_definition_id')->constrained();
            $table->unsignedSmallInteger('number');
            $table->string('chapter')->default('explore');
            $table->json('parameters');
            $table->json('mastery_rule');
            $table->timestamps();
            $table->unique(['game_definition_id', 'number']);
        });
        Schema::create('game_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained();
            $table->foreignId('game_definition_id')->constrained();
            $table->unsignedSmallInteger('current_level')->default(1);
            $table->unsignedSmallInteger('unlocked_level')->default(1);
            $table->json('mastery_history')->nullable();
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();
            $table->unique(['profile_id', 'game_definition_id']);
        });
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('level')->nullable()->after('status');
            $table->json('challenge_config')->nullable()->after('metrics');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) { $table->dropColumn(['level', 'challenge_config']); });
        Schema::dropIfExists('game_progress');
        Schema::dropIfExists('game_levels');
    }
};
