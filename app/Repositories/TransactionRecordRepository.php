<?php

namespace App\Repositories;

use App\Models\ImportError;
use App\Models\TransactionRecord;
use App\Support\ImportResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class TransactionRecordRepository
{
    /**
     * @param int $customerId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTransactionByCustomerId(int $customerId, int $perPage = 10): LengthAwarePaginator
    {
        return TransactionRecord::query()
            ->where('customer_id', $customerId)
            ->orderBy('date', 'desc')
            ->paginate($perPage, [
                'transaction_uid',
                'date',
                'content',
                'amount',
                'type',
            ]);
    }
    
    /**
     * @param $queueId
     * @param array $records
     * @return ImportResult
     */
    public function saveMultiple($queueId, array $records): ImportResult
    {
        $rules = (new TransactionRecord())->rules();
        $insertRows = [];
        $errorRows = [];
        $exitsUids = [];
        $createdAt = now();

        foreach ($records as $record) {
            try {
                $payload = [
                    'transaction_uid' => $this->createTransactionUid($record),
                    'customer_id' => $record['customer_id'],
                    'date' => $record['date'],
                    'content' => $record['content'],
                    'amount' => $record['amount'],
                    'type' => $record['type'],
                    'created_at' => $record['created_at'],
                ];

                Validator::make($payload, $rules)->validate();

                $uid = (string) $payload['transaction_uid'];

                if (isset($exitsUids[$uid])) {
                    $errorRows[] = $this->buildErrorRow($queueId, $record, 'Duplicate transaction in file.', $createdAt);
                    continue;
                }

                $exitsUids[$uid] = $record;
                $insertRows[$uid] = $payload;
            } catch (ValidationException $exception) {
                $message = collect($exception->errors())->flatten()->first() ?: $exception->getMessage();
                $errorRows[] = $this->buildErrorRow($queueId, $record, (string) $message, $createdAt);
            } catch (Throwable $exception) {
                $errorRows[] = $this->buildErrorRow($queueId, $record, $exception->getMessage(), $createdAt);
            }
        }

        if (!empty($insertRows)) {
            $existingUids = TransactionRecord::query()
                ->whereIn('transaction_uid', array_keys($insertRows))
                ->pluck('transaction_uid')
                ->all();

            foreach ($existingUids as $existingUid) {
                $record = $exitsUids[$existingUid];
                $errorRows[] = $this->buildErrorRow($queueId, $record, 'Duplicate transaction already exists.', $createdAt);
                unset($insertRows[$existingUid]);
            }
        }

        if (!empty($insertRows)) {
            TransactionRecord::query()->insert(array_values($insertRows));
        }

        if (!empty($errorRows)) {
            ImportError::query()->insert($errorRows);
        }

        return new ImportResult(count($insertRows), count($errorRows));
    }
    
    /**
     * @param $queueId
     * @param array $record
     * @param string $message
     * @param $createdAt
     * @return array
     */
    protected function buildErrorRow($queueId, array $record, string $message, $createdAt): array
    {
        return [
            'queue_id' => (string) $queueId,
            'row_number' => (int) ($record['row_number'] ?? 0),
            'row_date' => $this->formatDate($record['row_date'] ?? null),
            'row_content' => $record['row_content'] ?? null,
            'error_message' => $message,
            'created_at' => $createdAt,
        ];
    }
    
    /**
     * @param $value
     * @return string|null
     */
    protected function formatDate($value)
    {
        if (empty($value)) {
            return null;
        }

        $timestamp = strtotime((string) $value);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
    
    /**
     * @param array $record
     * @return string
     */
    protected function createTransactionUid(array $record): string
    {
        $parts = [
            (string) ($record['customer_id'] ?? ''),
            trim((string) ($record['date'] ?? '')),
            trim((string) ($record['content'] ?? '')),
            trim((string) ($record['amount'] ?? '')),
            (string) ($record['type'] ?? ''),
        ];

        return hash('sha256', implode('|', $parts));
    }
}
