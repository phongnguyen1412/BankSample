<?php

return [
    App\Providers\AppServiceProvider::class,
    Laravel\Sanctum\SanctumServiceProvider::class,
    NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider::class,
    VladimirYuldashev\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider::class,
];
