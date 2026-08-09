<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    protected $fillable = [
        'workflow_id',
        'name',
        'sequence',
        'step_type',
        'approval_mode',
        'roles',
        'assigned_to',
        'status',
        'approved_by',
        'approved_at',
        'comments',
    ];

    protected $casts = [
        'roles' => 'array',
        'approved_at' => 'datetime',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
