<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    protected $fillable = [
        'workflowable_type',
        'workflowable_id',
        'definition_key',
        'status',
        'current_step',
        'submitted_by',
        'submitted_at',
        'completed_at',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('sequence');
    }

    public function currentStep()
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step');
    }

    public function workflowable()
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
