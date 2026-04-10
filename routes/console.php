<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('testing:create-users', function () {
    User::query()->updateOrCreate(
        [
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        ],
        [
            'name' => env('ADMIN_NAME', 'Admin'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'admin123')),
        ]
    );

    $emails = [
        'customer@example.com',
        'customer1@example.com',
        'customer2@example.com',
        'customer3@example.com',
        'customer4@example.com',
        'customer5@example.com',
        'customer6@example.com',
        'customer7@example.com',
        'customer8@example.com',
        'customer9@example.com',
        'customer10@example.com',
    ];

    foreach ($emails as $index => $email) {
        Customer::query()->updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => 'Customer ' . ($index + 1),
                'password' => Hash::make('123456'),
            ]
        );
    }

    $this->info('Test admin user and sample customers created successfully.');
})->purpose('Create test admin user and sample customers for local testing.');
