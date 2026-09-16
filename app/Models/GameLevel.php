<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameLevel extends Model
{
    protected $fillable = ['game_definition_id', 'number', 'chapter', 'parameters', 'mastery_rule'];
    protected $casts = ['number' => 'int', 'parameters' => 'array', 'mastery_rule' => 'array'];
    public function game() { return $this->belongsTo(GameDefinition::class, 'game_definition_id'); }
}
