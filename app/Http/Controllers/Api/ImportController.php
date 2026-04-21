<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportCsvRequest;
use App\Jobs\ImportCsvJob;
use App\Models\QueueStatus;
use App\Services\Csv\CsvFileValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportController extends Controller
{
    public function __construct(
        protected CsvFileValidator $csvFileValidator
    )
    {
    }
    
    /**
     * Import Csv
     *
     * @param ImportCsvRequest $request
     * @return JsonResponse
     */
    public function __invoke(ImportCsvRequest $request): JsonResponse
    {
        $file = $request->file('file');

        if (empty($file) || $file->getSize() === 0) {
            return $this->errorResponse('The uploaded file is empty.', 422, [
                'file' => ['The uploaded file is empty.'],
            ]);
        }

        try {
            $this->csvFileValidator->validateHeader((string) $file->getRealPath());

            $disk = (string) config('imports.disk', 'local');
            $directory = trim((string) config('imports.directory', 'imports'), '/');
            $filename = now()->format('YmdHis')
                . '_'
                . Str::uuid()->toString()
                . '.'
                . ($file->getClientOriginalExtension() ?: 'csv');
            $path = Storage::disk($disk)->putFileAs($directory, $file, $filename);

            $queueId = Str::upper(Str::random(24));

            QueueStatus::query()->create([
                'queue_id' => $queueId,
                'file_path' => $path,
                'status' => QueueStatus::STATUS_PENDING,
                'created_at' => now(),
            ]);

            ImportCsvJob::dispatch($queueId, $disk, $path, $file->getClientOriginalName());

            return $this->successResponse('File uploaded and queued successfully.', [
                'queue_id' => $queueId,
                'status_label' => QueueStatus::label(QueueStatus::STATUS_PENDING),
                'original_name' => $file->getClientOriginalName(),
            ], 202);
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                $exception->getMessage(),
                422,
                $exception->errors()
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse('Server error. Please try again later.', 500);
        }
    }
}
