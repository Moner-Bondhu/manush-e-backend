<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'theme',
        'skill_tags',
        'duration_seconds',
        'min_grade',
        'max_grade',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'skill_tags' => 'array',
        'duration_seconds' => 'int',
        'min_grade' => 'int',
        'max_grade' => 'int',
        'sort_order' => 'int',
    ];

    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }

    public function levels()
    {
        return $this->hasMany(GameLevel::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
