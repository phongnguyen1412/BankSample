<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCsvRequest extends FormRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
                'extensions:csv,txt',
            ],
        ];
    }
    
    /**
     * @return array[]
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File is required.',
            'file.file' => 'Invalid file upload.',
            'file.mimetypes' => 'Only CSV file is allowed.',
            'file.extensions' => 'Only csv',
        ];
    }
}
