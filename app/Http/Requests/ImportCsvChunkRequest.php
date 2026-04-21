<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCsvChunkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'upload_id' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9_-]+$/',
            ],
            'chunk' => [
                'required',
                'file',
            ],
            'chunk_index' => [
                'required',
                'integer',
                'min:0',
            ],
            'total_chunks' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],
            'original_name' => [
                'required',
                'string',
                'max:255',
                'regex:/\.csv$/i',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'upload_id.required' => 'Upload ID is required.',
            'upload_id.regex' => 'Upload ID may only contain letters, numbers, dash, and underscore.',
            'chunk.required' => 'Chunk file is required.',
            'chunk.file' => 'Invalid chunk upload.',
            'chunk_index.required' => 'Chunk index is required.',
            'chunk_index.integer' => 'Chunk index must be a number.',
            'chunk_index.min' => 'Chunk index must start from 0.',
            'total_chunks.required' => 'Total chunks is required.',
            'total_chunks.integer' => 'Total chunks must be a number.',
            'total_chunks.min' => 'Total chunks must be at least 1.',
            'total_chunks.max' => 'Total chunks is too large.',
            'original_name.required' => 'Original file name is required.',
            'original_name.regex' => 'Only .csv file extension is allowed.',
        ];
    }
}
