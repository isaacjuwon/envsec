<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution Strategies
    |--------------------------------------------------------------------------
    |
    | This option defines the order in which the application will attempt to
    | resolve the current tenant. The first strategy to return a valid
    | workspace will be used.
    |
    | Supported: "path", "session", "host"
    |
    */
    'resolution_strategy' => [
        'path',
        'session',
        'host',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Storage Key
    |--------------------------------------------------------------------------
    |
    | The key used to store the current tenant ID in the session.
    |
    */
    'session_key' => 'tenant_id',

    /*
    |--------------------------------------------------------------------------
    | Force Path Tenancy
    |--------------------------------------------------------------------------
    |
    | When enabled, the application will automatically redirect users to
    | the path-based tenanted URL if they are resolved via session 
    | or host but the current URL path is not tenanted.
    |
    */
    'force_path' => true,
];
