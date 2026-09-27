<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExperienceBlueprint extends Model
{
    protected $fillable = [
        'teacher_user_id', 'learning_group_id', 'title', 'subject', 'grade_band',
        'focus_area', 'objective', 'duration_weeks', 'language', 'status',
        'success_criteria', 'student_context',
    ];

    protected $casts = [
        'success_criteria' => 'array',
        'student_context' => 'array',
    ];

    public function blocks()
    {
        return $this->hasMany(ExperienceBlock::class)->orderBy('week_number')->orderBy('sort_order');
    }

    public function learningGroup()
    {
        return $this->belongsTo(LearningGroup::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }
}
