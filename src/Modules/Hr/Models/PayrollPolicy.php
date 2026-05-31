<?php

namespace App\Modules\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Modules\Hr\Models\PayrollPolicy;
use App\Modules\Hr\Models\PayrollPolicyAssignment;

use Illuminate\Database\Eloquent\Model;


class PayrollPolicy extends Model 
{
    use HasFactory;
    
    use SoftDeletes;

    

    protected $table = 'payroll_policies';
    
    
    
    public $timestamps = true;
    

    protected $fillable = [
        'name', 'type', 'effect', 'description', 'country_code', 'state_code', 'calculation_logic', 'employer_ratio', 'is_statutory', 'effective_date', 'expiry_date', 'is_active', 'parent_policy_id'
    ];

    protected $guarded = [
        
    ];

    protected $casts = [
        'employer_ratio' => 'decimal:2',
        'is_statutory' => 'boolean',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer'
    ];

    protected $attributes = [
        'effect' => 'addition',
        'country_code' => 'US',
        'is_statutory' => false,
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

    public function parentPolicy()
    {
        return $this->belongsTo(\App\Modules\Hr\Models\PayrollPolicy::class, 'parent_policy_id', 'id');
    }

    public function childPolicies()
    {
        return $this->hasMany(\App\Modules\Hr\Models\PayrollPolicy::class, 'parent_policy_id', 'id');
    }

    public function assignments()
    {
        return $this->hasMany(\App\Modules\Hr\Models\PayrollPolicyAssignment::class, 'payroll_policy_id', 'id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \App\Modules\Hr\Database\Factories\PayrollPolicyFactory::new();
    }
}