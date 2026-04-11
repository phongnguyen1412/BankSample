<?php

namespace App\Services\Csv;

use Illuminate\Support\Str;

class CsvHeaderNormalizer
{
    /**
     * Normalize Header
     *
     * @param array $headers
     * @return array
     */
    public function normalize(array $headers): array
    {
        $normalized = [];
        $counts = [];

        foreach (array_values($headers) as $index => $header) {
            $base = Str::of((string) $header)->trim()->snake()->value();

            if (empty($base)) {
                $base = 'column_' . ($index + 1);
            }

            $counts[$base] = ($counts[$base] ?? 0) + 1;
            $normalized[] = $counts[$base] === 1 ? $base : $base . '_' . $counts[$base];
        }

        return $normalized;
    }
}
