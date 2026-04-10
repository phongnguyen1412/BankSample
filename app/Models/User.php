<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var array[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var array[]
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
