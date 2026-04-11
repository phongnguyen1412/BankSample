<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportCsvRequest;
use App\Jobs\ImportCsvJob;
use App\Models\QueueStatus;
use App\Services\Csv\CsvHeaderNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportController extends Controller
{
    /**
     * @var CsvHeaderNormalizer
     */
    protected $headerNormalizer;
    
    /**
     * @param CsvHeaderNormalizer $headerNormalizer
     */
    public function __construct(CsvHeaderNormalizer $headerNormalizer)
    {
        $this->headerNormalizer = $headerNormalizer;
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
            return response()->json([
                'message' => 'The uploaded file is empty.',
            ], 422);
        }

        try {
            $this->validateCsvHeader($file->getRealPath());

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

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and queued successfully.',
                'queue_id' => $queueId,
                'status_label' => QueueStatus::label(QueueStatus::STATUS_PENDING),
                'original_name' => $file->getClientOriginalName(),
            ], 202);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Server error. Please try again later.',
            ], 500);
        }
    }

    /**
     * Validate Header
     *
     * @param $realPath
     * @return void
     * @throws ValidationException
     */
    protected function validateCsvHeader($realPath): void
    {
        if (empty($realPath) || ! is_readable($realPath)) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read the uploaded file.',
            ]);
        }

        $handle = fopen($realPath, 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'Unable to read the uploaded file.',
            ]);
        }

        try {
            $firstRow = fgetcsv($handle);
        } finally {
            fclose($handle);
        }

        if ($firstRow === false) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file is empty.',
            ]);
        }

        $normalizedHeader = $this->headerNormalizer->normalize($firstRow);
        $requiredColumns = ['date', 'content', 'amount', 'type', 'customer_email'];
        $missingColumns = array_values(array_diff($requiredColumns, $normalizedHeader));

        if (!empty($missingColumns)) {
            throw ValidationException::withMessages([
                'file' => 'Missing required columns: ' . implode(', ', $missingColumns),
            ]);
        }
    }
}
