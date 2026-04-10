<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportError extends Model
{
    /**
     * @var string
     */
    protected $table = 'import_error';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array[]
     */
    protected $fillable = [
        'queue_id',
        'row_number',
        'row_date',
        'row_content',
        'error_message',
        'created_at',
    ];
}
