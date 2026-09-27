<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileUserAssignment extends Model
{
    protected $fillable = ['profile_id', 'user_id', 'role', 'relationship', 'is_primary'];
    protected $casts = ['is_primary' => 'boolean'];

    public function profile() { return $this->belongsTo(Profile::class); }
    public function user() { return $this->belongsTo(User::class); }
}
