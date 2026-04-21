<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportCsvChunkRequest;
use App\Jobs\ImportCsvJob;
use App\Models\QueueStatus;
use App\Services\Csv\CsvFileValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportChunkController extends Controller
{
    public function __construct(
        protected CsvFileValidator $csvFileValidator
    ) {
    }

    public function __invoke(ImportCsvChunkRequest $request): JsonResponse
    {
        $disk = (string) config('imports.disk', 'local');
        $uploadId = (string) $request->input('upload_id');
        $chunkIndex = (int) $request->input('chunk_index');
        $totalChunks = (int) $request->input('total_chunks');
        $originalName = (string) $request->input('original_name');

        if ($chunkIndex >= $totalChunks) {
            return $this->errorResponse('Chunk index must be smaller than total chunks.', 422, [
                'chunk_index' => ['Chunk index must be smaller than total chunks.'],
            ]);
        }

        try {
            $this->saveChunk($disk, $uploadId, $chunkIndex, $request);
            $receivedChunks = $this->countReceivedChunks($disk, $uploadId, $totalChunks);

            if ($receivedChunks < $totalChunks) {
                return $this->successResponse('Chunk uploaded successfully.', [
                    'upload_id' => $uploadId,
                    'chunk_index' => $chunkIndex,
                    'received_chunks' => $receivedChunks,
                    'total_chunks' => $totalChunks,
                    'completed' => false,
                ], 202);
            }

            $path = $this->assembleChunks($disk, $uploadId, $totalChunks);
            $this->csvFileValidator->validateHeader(Storage::disk($disk)->path($path));

            $queueId = Str::upper(Str::random(24));

            QueueStatus::query()->create([
                'queue_id' => $queueId,
                'file_path' => $path,
                'status' => QueueStatus::STATUS_PENDING,
                'created_at' => now(),
            ]);

            ImportCsvJob::dispatch($queueId, $disk, $path, $originalName);
            Storage::disk($disk)->deleteDirectory($this->chunkDirectory($uploadId));

            return $this->successResponse('File uploaded and queued successfully.', [
                'queue_id' => $queueId,
                'status_label' => QueueStatus::label(QueueStatus::STATUS_PENDING),
                'original_name' => $originalName,
                'upload_id' => $uploadId,
                'completed' => true,
            ], 202);
        } catch (ValidationException $exception) {
            if (isset($path)) {
                Storage::disk($disk)->delete($path);
            }

            return $this->errorResponse($exception->getMessage(), 422, $exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse('Server error. Please try again later.', 500);
        }
    }

    protected function saveChunk(
        string $disk,
        string $uploadId,
        int $chunkIndex,
        ImportCsvChunkRequest $request
    ): void {
        Storage::disk($disk)->putFileAs(
            $this->chunkDirectory($uploadId),
            $request->file('chunk'),
            $this->chunkFileName($chunkIndex)
        );
    }

    protected function countReceivedChunks(string $disk, string $uploadId, int $totalChunks): int
    {
        $received = 0;

        for ($index = 0; $index < $totalChunks; $index++) {
            if (Storage::disk($disk)->exists($this->chunkPath($uploadId, $index))) {
                $received++;
            }
        }

        return $received;
    }

    protected function assembleChunks(string $disk, string $uploadId, int $totalChunks): string
    {
        $directory = trim((string) config('imports.directory', 'imports'), '/');
        $path = $directory . '/' . now()->format('YmdHis') . '_' . Str::uuid()->toString() . '.csv';

        Storage::disk($disk)->makeDirectory($directory);

        $fullPath = Storage::disk($disk)->path($path);
        $output = fopen($fullPath, 'wb');

        if ($output === false) {
            throw ValidationException::withMessages([
                'file' => 'Unable to prepare the uploaded file.',
            ]);
        }

        try {
            for ($index = 0; $index < $totalChunks; $index++) {
                $chunkPath = Storage::disk($disk)->path($this->chunkPath($uploadId, $index));
                $input = fopen($chunkPath, 'rb');

                if ($input === false) {
                    throw ValidationException::withMessages([
                        'chunk' => 'Missing chunk: ' . $index,
                    ]);
                }

                stream_copy_to_stream($input, $output);
                fclose($input);
            }
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        } finally {
            fclose($output);
        }

        return $path;
    }

    protected function chunkDirectory(string $uploadId): string
    {
        $directory = trim((string) config('imports.directory', 'imports'), '/');

        return $directory . '/chunks/' . $uploadId;
    }

    protected function chunkPath(string $uploadId, int $chunkIndex): string
    {
        return $this->chunkDirectory($uploadId) . '/' . $this->chunkFileName($chunkIndex);
    }

    protected function chunkFileName(int $chunkIndex): string
    {
        return $chunkIndex . '.part';
    }
}
