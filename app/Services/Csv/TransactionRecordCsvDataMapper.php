<?php

namespace App\Services\Csv;

use App\Repositories\CustomerRepository;
use Carbon\CarbonImmutable;
use Nette\Schema\ValidationException;
use RuntimeException;

class TransactionRecordCsvDataMapper
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @param CustomerRepository $customerRepository
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Mapping header with row data
     *
     * @param array $header
     * @param array $row
     * @return array
     */
    public function map(array $header, array $row): array
    {
        $payload = $this->combineHeaderAndRow($header, $row);

        return [
            'customer_id' => $this->mapCustomerId($payload),
            'date' => $this->formatDate($this->getValue($payload, 'date')),
            'content' => trim($this->getValue($payload, 'content')),
            'amount' => trim($this->getValue($payload, 'amount')),
            'type' => $this->mapType($this->getValue($payload, 'type')),
        ];
    }

    /**
     * Combine Header And Row
     *
     * @param array $header
     * @param array $row
     * @return array
     */
    protected function combineHeaderAndRow(array $header, array $row): array
    {
        $row = array_values($row);

        if (count($row) > count($header)) {
            for ($index = count($header); $index < count($row); $index++) {
                $header[] = 'column_' . ($index + 1);
            }
        }

        $normalizedRow = array_pad($row, count($header), null);
        $payload = array_combine($header, $normalizedRow);

        if (empty($payload)) {
            throw new ValidationException('Cannot map CSV row to columns.');
        }

        return $payload;
    }

    /**
     * Get Value
     *
     * @param array $payload
     * @param string $key
     * @return mixed
     */
    protected function getValue(array $payload, string $key)
    {
        if (!array_key_exists($key, $payload)) {
            return '';
        }

        return $payload[$key];
    }

    /**
     * Format Date
     *
     * @param mixed $value
     * @return string
     */
    protected function formatDate($value): string
    {
        if (empty($value)) {
            throw new ValidationException('CSV row is missing date.');
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d H:i:s', trim($value))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            throw new ValidationException('CSV row has an invalid date format. Expected Y-m-d H:i:s.');
        }
    }

    /**
     *
     * Map Type
     *
     * @param mixed $value
     * @return int
     */
    protected function mapType($value): int
    {
        $rawValue = trim((string)$value);
        $normalizedValue = strtolower($rawValue);

        switch ($normalizedValue) {
            case 'deposit':
            case '1':
                return 1;
            case 'withdraw':
            case '2':
                return 2;
            default:
                throw new ValidationException(
                    "CSV row has invalid type value [{$rawValue}]. Expected deposit/1 or withdraw/2."
                );
        }
    }

    /**
     * Map Customer Id
     *
     * @param array $payload
     * @return int
     */
    protected function mapCustomerId(array $payload): int
    {
        $email = strtolower(trim((string)$this->getValue($payload, 'customer_email')));

        if (empty($email)) {
            throw new ValidationException('CSV row is missing customer email.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException("CSV row has invalid customer email [{$email}].");
        }

        $customerId = $this->customerRepository->findIdByEmail($email);

        if (empty($customerId)) {
            throw new ValidationException("Customer not found for email [{$email}].");
        }

        return $customerId;
    }
}
