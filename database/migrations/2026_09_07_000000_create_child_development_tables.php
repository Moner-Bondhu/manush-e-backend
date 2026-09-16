<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained();
            $table->string('event_type');
            $table->json('payload');
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['game_session_id', 'event_type']);
        });

        Schema::create('child_feature_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained();
            $table->string('domain');
            $table->string('feature');
            $table->decimal('value', 8, 3);
            $table->string('source_type');
            $table->foreignId('source_id')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();
            $table->index(['profile_id', 'domain', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_feature_snapshots');
        Schema::dropIfExists('game_events');
    }
};
