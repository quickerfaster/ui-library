<?php

namespace QuickerFaster\UILibrary\Core\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'state_code',
        'country_code',
        'postal_code',
        'tax_id',
        'registration_number',
        'currency_code',
        'timezone',
        'date_format',
        'is_active',
        'status',
        'metadata',
        'level',
        'parent_company_id',
        'billing_email',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state_code',
        'billing_postal_code',
        'billing_country_code',
        'database_name',
        'is_placeholder',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_placeholder' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'status' => 'active',
        'currency_code' => 'USD',
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d',
        'level' => 'division',
        'is_placeholder' => true,
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

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }
}