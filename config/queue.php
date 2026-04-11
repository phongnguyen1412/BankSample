<?php

return [
    'default' => env('QUEUE_CONNECTION', 'rabbitmq'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'queue' => env('RABBITMQ_QUEUE', 'csv-imports'),
            'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,

            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => env('RABBITMQ_PORT', 5672),
                    'user' => env('RABBITMQ_USER', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],

            'options' => [
                'heartbeat' => (int) env('RABBITMQ_HEARTBEAT', 30),
                'read_timeout' => (float) env('RABBITMQ_READ_WRITE_TIMEOUT', 60),
                'write_timeout' => (float) env('RABBITMQ_READ_WRITE_TIMEOUT', 60)
            ],

            'worker' => env('RABBITMQ_WORKER', 'default'),
        ],
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
