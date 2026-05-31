<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use App\Modules\Hr\Models\Employee;

use App\Models\User As DefaultUser;


class User extends DefaultUser 
{
    use HasFactory;
    
    use SoftDeletes;

    

    protected $table = 'users';
    
    
    
    public $timestamps = true;
    

    protected $fillable = [
        'name', 'email', 'status', 'password'
    ];

    protected $guarded = [
        
    ];

    protected $casts = [
        'email_verified_at' => 'datetime'
    ];

    protected $attributes = [
        
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

    public function employee()
    {
        return $this->hasOne(\App\Modules\Hr\Models\Employee::class, 'user_id', 'id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \App\Modules\Admin\Database\Factories\UserFactory::new();
    }
}