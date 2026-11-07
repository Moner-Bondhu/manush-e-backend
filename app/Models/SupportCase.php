<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportCase extends Model
{
    protected $fillable = [
        'profile_id', 'assigned_expert_user_id', 'service_path', 'status', 'priority',
        'rationale', 'professional_notes', 'consent_status', 'next_review_at', 'professional_formulation',
        'parent_summary', 'disclosure_status', 'disclosure_scope', 'child_assent_status',
        'parent_consent_at', 'parent_declined_at',
    ];

    protected $casts = [
        'next_review_at' => 'date', 'professional_formulation' => 'array', 'disclosure_scope' => 'array',
        'parent_consent_at' => 'datetime', 'parent_declined_at' => 'datetime',
    ];

    public function profile() { return $this->belongsTo(Profile::class); }
    public function assignedExpert() { return $this->belongsTo(User::class, 'assigned_expert_user_id'); }
}
