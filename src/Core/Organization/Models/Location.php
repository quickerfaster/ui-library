<?php

namespace QuickerFaster\UILibrary\Core\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'address',
        'city',
        'postal_code',
        'latitude',
        'longitude',
        'phone',
        'email',
        'timezone',
        'is_headquarters',
        'is_active',
        'metadata',
        'address_line_1',
        'address_line_2',
        'website',
        'is_remote',
        'capacity',
        'opening_hours',
        'opening_date',
        'closing_date',
        'country_code',
        'state_code',
    ];

    protected $casts = [
        'is_headquarters' => 'boolean',
        'is_active' => 'boolean',
        'is_remote' => 'boolean',
        'capacity' => 'integer',
        'metadata' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected $attributes = [
        'type' => 'office',
        'is_headquarters' => false,
        'is_active' => true,
        'is_remote' => false,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}