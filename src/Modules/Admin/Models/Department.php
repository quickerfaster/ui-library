<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Modules\Admin\Models\Department;
use App\Modules\Hr\Models\EmployeePosition;
use App\Modules\Admin\Models\Company;

use Illuminate\Database\Eloquent\Model;


class Department extends Model 
{
    use HasFactory;
    
    use SoftDeletes;

    

    protected $table = 'departments';
    
    
    
    public $timestamps = true;
    

    protected $fillable = [
        'name', 'code', 'description', 'company_id', 'parent_department_id', 'cost_center', 'is_active'
    ];

    protected $guarded = [
        
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    protected $attributes = [
        'is_active' => true
    ];

    protected $dispatchesEvents = [
        
    ];

    /**
     * Validation rules for the model.
     */
    protected static $rules = [
        
    ];

    /**
     * Custom validation messages.
     */
    protected static $messages = [
        
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
    }

    /**
     * Validate the model instance.
     */
    public function validate()
    {
        $validator = Validator::make($this->attributesToArray(), static::$rules, static::$messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return true;
    }

    /**
     * Save the model to the database with validation.
     */
    public function save(array $options = [])
    {
        $this->validate();
        return parent::save($options);
    }

    public function parentDepartment()
    {
        return $this->belongsTo(\App\Modules\Admin\Models\Department::class, 'parent_department_id', 'id');
    }

    public function employeePositions()
    {
        return $this->hasMany(\App\Modules\Hr\Models\EmployeePosition::class, 'department_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Modules\Admin\Models\Company::class, 'company_id', 'id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \App\Modules\Admin\Database\Factories\DepartmentFactory::new();
    }
}