<?php

namespace QuickerFaster\UILibrary\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * QuickerFaster UI Library Role Model
 *
 * Extends Spatie's Role model to provide additional functionality
 * and a library-owned namespace for access control components.
 *
 * This replaces the App\Modules\Admin\Models\Role reference
 * used by AccessControlManager, PermissionManager, and related components.
 */
class Role extends SpatieRole
{
    use HasFactory;

    protected $table = 'roles';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'guard_name',
        'editable',
    ];

    protected $casts = [];

    protected $attributes = [];

    protected $dispatchesEvents = [];
}