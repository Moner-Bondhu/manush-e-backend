<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportCase extends Model
{
    protected $fillable = [
        'profile_id', 'assigned_expert_user_id', 'service_path', 'status', 'priority',
        'rationale', 'professional_notes', 'consent_status', 'next_review_at',
    ];

    protected $casts = ['next_review_at' => 'date'];

    public function profile() { return $this->belongsTo(Profile::class); }
    public function assignedExpert() { return $this->belongsTo(User::class, 'assigned_expert_user_id'); }
}
