<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowAction extends Model
{
    protected $fillable = [
        'workflow_id',
        'step_id',
        'user_id',
        'action',
        'comments',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function step()
    {
        return $this->belongsTo(WorkflowStep::class, 'step_id');
    }
}
