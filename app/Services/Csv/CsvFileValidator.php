<?php

namespace App\Services\Csv;

use Illuminate\Validation\ValidationException;

class CsvFileValidator
{
    public function __construct(
        protected CsvHeaderNormalizer $headerNormalizer
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validateHeader(string $realPath): void
    {
        if ($realPath === '' || ! is_readable($realPath)) {
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

        if (! empty($missingColumns)) {
            throw ValidationException::withMessages([
                'file' => 'Missing required columns: ' . implode(', ', $missingColumns),
            ]);
        }
    }
}
