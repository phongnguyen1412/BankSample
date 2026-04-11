<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueStatus extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_PARTIAL_ERROR = 2;
    public const STATUS_DONE = 3;

    /**
     * @var string
     */
    protected $table = 'queue_status';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array[]
     */
    protected $fillable = [
        'queue_id',
        'file_path',
        'status',
        'created_at',
    ];
    
    
    /**
     * Get Label
     *
     * @param int $status
     * @return string
     */
    public static function label(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_PARTIAL_ERROR => 'Partial Error',
            self::STATUS_DONE => 'Done',
            default => 'Unknown',
        };
    }
}
