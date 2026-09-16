<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_definition_id',
        'profile_id',
        'status',
        'level',
        'duration_ms',
        'metrics',
        'challenge_config',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'game_definition_id' => 'int',
        'profile_id' => 'int',
        'duration_ms' => 'int',
        'level' => 'int',
        'metrics' => 'array',
        'challenge_config' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function game()
    {
        return $this->belongsTo(GameDefinition::class, 'game_definition_id');
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function events()
    {
        return $this->hasMany(GameEvent::class);
    }
}
