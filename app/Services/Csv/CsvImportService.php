<?php

namespace App\Services\Csv;

use App\Models\ImportError;
use App\Repositories\TransactionRecordRepository;
use App\Support\ImportResult;
use Illuminate\Support\Facades\Storage;
use Nette\Schema\ValidationException;
use RuntimeException;

class CsvImportService
{
    /**
     * @var CsvHeaderNormalizer
     */
    protected $headerNormalizer;

    /**
     * @var TransactionRecordCsvDataMapper
     */
    protected $dataMapper;

    /**
     * @var TransactionRecordRepository
     */
    protected $repository;
    
    /**
     * @param CsvHeaderNormalizer $headerNormalizer
     * @param TransactionRecordCsvDataMapper $dataMapper
     * @param TransactionRecordRepository $repository
     */
    public function __construct(
        CsvHeaderNormalizer $headerNormalizer,
        TransactionRecordCsvDataMapper $dataMapper,
        TransactionRecordRepository $repository
    ) {
        $this->headerNormalizer = $headerNormalizer;
        $this->dataMapper = $dataMapper;
        $this->repository = $repository;
    }

    /**
     * Import
     *
     * @param string $disk
     * @param string $path
     * @param $queueId
     * @return ImportResult
     */
    public function import(string $disk, string $path, $queueId): ImportResult
    {
        $filePath = Storage::disk($disk)->path($path);
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new ValidationException("Cannot open CSV file [{$path}].");
        }

        $header = null;
        $rowNumber = 0;
        $chunk = [];
        $errorRows = [];
        $successCount = 0;
        $errorCount = 0;

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row)) {
                    continue;
                }

                if (empty($header)) {
                    $header = $this->headerNormalizer->normalize($row);
                    continue;
                }

                $rowNumber++;
                $rawRow = $this->extractRawFields($header, $row);

                try {
                    $mappedRow = $this->dataMapper->map($header, $row);
                    $mappedRow['row_number'] = $rowNumber;
                    $mappedRow['row_date'] = $rawRow['row_date'];
                    $mappedRow['row_content'] = $rawRow['row_content'];
                    $chunk[] = $mappedRow;
                } catch (ValidationException $exception) {
                    $errorRows[] = $this->buildImportErrorRow(
                        $queueId,
                        $rowNumber,
                        $rawRow['row_date'],
                        $rawRow['row_content'],
                        $exception->getMessage()
                    );
                    $errorCount++;

                    if (count($errorRows) >= $this->errorBatchSize()) {
                        ImportError::query()->insert($errorRows);
                        $errorRows = [];
                    }

                    continue;
                }

                if (count($chunk) >= $this->chunkSize()) {
                    $result = $this->repository->saveMultiple($queueId, $chunk);
                    $successCount += $result->successCount;
                    $errorCount += $result->errorCount;
                    $chunk = [];
                }
            }

            if (empty($header)) {
                throw new ValidationException('CSV file is empty or missing a header row.');
            }

            if (!empty($chunk)) {
                $result = $this->repository->saveMultiple($queueId, $chunk);
                $successCount += $result->successCount;
                $errorCount += $result->errorCount;
            }

            if (!empty($errorRows)) {
                ImportError::query()->insert($errorRows);
            }
        } finally {
            fclose($handle);
        }

        return new ImportResult($successCount, $errorCount);
    }

    /**
     * Extract Raw Values
     *
     * @param array $header
     * @param array $row
     * @return null[]
     */
    protected function extractRawFields(array $header, array $row): array
    {
        $normalizedRow = array_pad(array_values($row), count($header), null);
        $payload = array_combine($header, $normalizedRow);

        if (empty($payload)) {
            return [
                'row_date' => null,
                'row_content' => null,
            ];
        }

        return [
            'row_date' => isset($payload['date']) ? trim((string)$payload['date']) : null,
            'row_content' => isset($payload['content']) ? trim((string)$payload['content']) : null,
        ];
    }

    /**
     * Build Error Row
     *
     * @param $queueId
     * @param int $rowNumber
     * @param $rowDate
     * @param $rowContent
     * @param string $message
     * @return array
     */
    protected function buildImportErrorRow($queueId, int $rowNumber, $rowDate, $rowContent, string $message): array
    {
        return [
            'queue_id' => (string)$queueId,
            'row_number' => $rowNumber,
            'row_date' => $rowDate,
            'row_content' => $rowContent,
            'error_message' => $message,
            'created_at' => now(),
        ];
    }

    /**
     * @return int
     */
    protected function chunkSize(): int
    {
        return max(1, (int)config('imports.chunk_size', 1000));
    }

    /**
     * @return int
     */
    protected function errorBatchSize(): int
    {
        return max(100, (int)config('imports.error_batch_size', 1000));
    }
}
