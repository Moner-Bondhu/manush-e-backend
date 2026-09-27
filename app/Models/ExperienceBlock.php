<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExperienceBlock extends Model
{
    protected $fillable = [
        'experience_blueprint_id', 'week_number', 'sort_order', 'block_type', 'title',
        'description', 'student_prompt', 'teacher_note', 'duration_minutes', 'personalization',
    ];

    protected $casts = ['personalization' => 'array'];

    public function blueprint()
    {
        return $this->belongsTo(ExperienceBlueprint::class, 'experience_blueprint_id');
    }
}
