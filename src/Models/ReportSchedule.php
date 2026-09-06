<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Model;

class ReportSchedule extends Model
{
    protected $fillable = [
        'name', 'report_type', 'parameters', 'frequency',
        'time', 'timezone', 'recipients', 'last_run_at',
        'next_run_at', 'status',
    ];

    protected $casts = [
        'parameters' => 'array',
        'recipients' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDue(): bool
    {
        return $this->isActive() && $this->next_run_at && $this->next_run_at->isPast();
    }

    public function markRun(): void
    {
        $this->last_run_at = now();
        $this->next_run_at = $this->calculateNextRun();
        $this->save();
    }

    public function markFailed(): void
    {
        $this->status = 'failed';
        $this->save();
    }

    protected function calculateNextRun(): ?\Carbon\Carbon
    {
        if (!$this->frequency) return null;

        return match ($this->frequency) {
            'daily' => now()->addDay()->setTimeFromTimeString($this->time ?? '00:00'),
            'weekly' => now()->addWeek()->setTimeFromTimeString($this->time ?? '00:00'),
            'monthly' => now()->addMonth()->setTimeFromTimeString($this->time ?? '00:00'),
            'quarterly' => now()->addQuarter()->setTimeFromTimeString($this->time ?? '00:00'),
            default => null,
        };
    }
}