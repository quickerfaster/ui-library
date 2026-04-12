<?php

namespace App\Modules\System\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Modules\System\Models\Plan;
// use App\Modules\Admin\Models\User;


class Company extends Model
{
    use HasFactory;
    
    

    

    protected $table = 'companies';
    
    
    
    
    

    protected $fillable = [
        'name', 'subdomain', 'domain_verified', 'email_verification_token', 'email_verification_sent_at', 'status', 'plan_id', 'billing_email', 'billing_address_line_1', 'billing_address_line_2', 'billing_city', 'billing_state_province', 'billing_postal_code', 'billing_country_code', 'timezone', 'currency_code'
    ];

    protected $guarded = [
        
    ];

    protected $casts = [
        'domain_verified' => 'boolean',
        'email_verification_sent_at' => 'datetime'
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

    public function plan()
    {
        return $this->belongsTo(\App\Modules\System\Models\Plan::class, 'plan_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(\App\Modules\Admin\Models\User::class, 'company_id', 'id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \App\Modules\System\Database\Factories\CompanyFactory::new();
    }
}