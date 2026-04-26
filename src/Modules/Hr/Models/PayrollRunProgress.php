<?php

namespace App\Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;


class PayrollRunProgress extends Model
{
    protected $table = 'payroll_run_progress';
    protected $fillable = ['payroll_run_id', 'total_employees', 'processed_employees', 'status'];

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }
}