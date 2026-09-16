<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningGroup extends Model
{
    protected $fillable = ['teacher_user_id', 'name', 'school_name'];

    public function teacher() { return $this->belongsTo(User::class, 'teacher_user_id'); }
    public function profiles() { return $this->belongsToMany(Profile::class, 'learning_group_profiles'); }
}
