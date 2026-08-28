<?php

return [

    'auth' => [
        'module'   => 'auth',
        'host'     => env('FDW_AUTH_HOST', '127.0.0.1'),
        'port'     => env('FDW_AUTH_PORT', '5432'),
        'database' => env('FDW_AUTH_DATABASE', 'ses.auth'),
        'user'     => env('FDW_AUTH_USERNAME', 'postgres'),
        'password' => env('FDW_AUTH_PASSWORD', 'postgres'),
    ],

    'core' => [
        'module'   => 'core',
        'host'     => env('FDW_CORE_HOST', '127.0.0.1'),
        'port'     => env('FDW_CORE_PORT', '5432'),
        'database' => env('FDW_CORE_DATABASE', 'ses.core'),
        'user'     => env('FDW_CORE_USERNAME', 'postgres'),
        'password' => env('FDW_CORE_PASSWORD', 'postgres'),
    ],

    'datasus' => [
        'module'   => 'datasus',
        'host'     => env('FDW_DATASUS_HOST', '127.0.0.1'),
        'port'     => env('FDW_DATASUS_PORT', '5432'),
        'database' => env('FDW_DATASUS_DATABASE', 'ses.datasus'),
        'user'     => env('FDW_DATASUS_USERNAME', 'postgres'),
        'password' => env('FDW_DATASUS_PASSWORD', 'postgres'),
    ],

    'storage' => [
        'module'   => 'storage',
        'host'     => env('FDW_STORAGE_HOST', '127.0.0.1'),
        'port'     => env('FDW_STORAGE_PORT', '5432'),
        'database' => env('FDW_STORAGE_DATABASE', 'ses.storage'),
        'user'     => env('FDW_STORAGE_USERNAME', 'postgres'),
        'password' => env('FDW_STORAGE_PASSWORD', 'postgres'),
    ],

];