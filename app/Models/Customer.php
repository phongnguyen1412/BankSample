<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens;

    /**
     * @var string
     */
    protected $table = 'customer';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array[]
     */
    protected $fillable = [
        'email',
        'name',
        'password',
    ];

    /**
     * @var array[]
     */
    protected $hidden = [
        'password',
    ];
}
