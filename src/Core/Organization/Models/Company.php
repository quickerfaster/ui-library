<?php

namespace QuickerFaster\UILibrary\Core\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use QuickerFaster\UILibrary\Traits\HasSettings;

class Company extends Model
{
    use SoftDeletes, HasSettings;

    protected $fillable = [
        'name',
        'code',
        'subdomain',
        'logo',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_id',
        'registration_number',
        'currency',
        'timezone',
        'date_format',
        'is_active',
        'status',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'status' => 'active',
        'currency' => 'USD',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function businessUnits(): HasMany
    {
        return $this->hasMany(BusinessUnit::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }
}