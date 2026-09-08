<?php

/**
 * Admin Module — Permission Configuration
 *
 * Defines additional permissions beyond the auto-discovered CRUD permissions
 * for Admin module models. The 'extra' key is read by
 * AccessControlPermissionService::seedPermissionNames().
 *
 * @see \QuickerFaster\UILibrary\Services\AccessControl\AccessControlPermissionService
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Extra Permissions
    |--------------------------------------------------------------------------
    |
    | Permission names listed here are seeded in addition to the standard
    | view_*, create_*, edit_*, delete_*, print_*, export_*, import_* CRUD
    | permissions auto-discovered from the module's models.
    |
    */
    'extra' => [
        'create_invitation',
        'resend_invitation',
        'revoke_invitation',
    ],

];