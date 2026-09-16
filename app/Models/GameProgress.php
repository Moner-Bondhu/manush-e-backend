<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameProgress extends Model
{
    protected $table = 'game_progress';
    protected $fillable = ['profile_id', 'game_definition_id', 'current_level', 'unlocked_level', 'mastery_history', 'last_played_at'];
    protected $casts = ['current_level' => 'int', 'unlocked_level' => 'int', 'mastery_history' => 'array', 'last_played_at' => 'datetime'];
}
