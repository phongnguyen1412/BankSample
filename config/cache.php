<?php

use Illuminate\Support\Str;

return [
    'default' => env('CACHE_STORE', 'file'),

    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CONNECTION', 'mysql'),
            'table' => env('CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CONNECTION', 'mysql'),
            'lock_table' => env('CACHE_LOCK_TABLE', 'cache_locks'),
        ],
    ],

    'prefix' => env(
        'CACHE_PREFIX',
        Str::slug((string) env('APP_NAME', 'laravel'), '_') . '_cache'
    ),
];
