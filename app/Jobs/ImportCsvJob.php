<?php

namespace App\Jobs;

use App\Models\QueueStatus;
use App\Services\Csv\CsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $queueId;

    public $disk;

    public $path;

    public $originalName;

    public $tries = 1;
    
    /**
     * @param string $queueId
     * @param string $disk
     * @param string $path
     * @param string $originalName
     */
    public function __construct(string $queueId, string $disk, string $path, string $originalName)
    {
        $this->queueId = $queueId;
        $this->disk = $disk;
        $this->path = $path;
        $this->originalName = $originalName;

        $this->onConnection('rabbitmq');
        $this->onQueue((string) config('queue.connections.rabbitmq.queue', 'csv-imports'));
    }
    
    /**
     * @param CsvImportService $csvImportService
     * @return void
     */
    public function handle(CsvImportService $csvImportService): void
    {
        QueueStatus::query()
            ->where('queue_id', $this->queueId)
            ->update([
                'status' => QueueStatus::STATUS_PROCESSING,
            ]);

        $result = $csvImportService->import($this->disk, $this->path, $this->queueId);

        QueueStatus::query()
            ->where('queue_id', $this->queueId)
            ->update([
                'status' => $result->getStatus(),
            ]);
    }
    
    /**
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        report($exception);

        QueueStatus::query()
            ->where('queue_id', $this->queueId)
            ->update([
                'status' => QueueStatus::STATUS_PARTIAL_ERROR,
            ]);
    }
}
