<?php

return [
    'disk' => env('IMPORT_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
    'directory' => env('IMPORT_STORAGE_DIRECTORY', 'imports'),
    'chunk_size' => (int) env('CSV_IMPORT_CHUNK_SIZE', 1000),
    'error_batch_size' => (int) env('CSV_IMPORT_ERROR_BATCH_SIZE', 1000),
    'log_every_chunks' => (int) env('CSV_IMPORT_LOG_EVERY_CHUNKS', 10),
];
