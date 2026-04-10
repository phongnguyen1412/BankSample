<?php

namespace App\Support;

use App\Models\QueueStatus;

class ImportResult
{
    /**
     * @var int
     */
    public $successCount;

    /**
     * @var int
     */
    public $errorCount;

    /**
     * @param int $successCount
     * @param int $errorCount
     */
    public function __construct($successCount, $errorCount)
    {
        $this->successCount = $successCount;
        $this->errorCount = $errorCount;
    }
    
    /**
     * Get Status
     *
     * @return int
     */
    public function getStatus(): int
    {
        if ($this->successCount > 0 && $this->errorCount === 0) {
            return QueueStatus::STATUS_DONE;
        }

        return QueueStatus::STATUS_PARTIAL_ERROR;
    }
}
