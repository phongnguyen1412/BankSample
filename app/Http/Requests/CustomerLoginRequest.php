<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerLoginRequest extends FormRequest
{
    /**
     * Rules
     *
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get Message
     *
     * @return array[]
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Customer email is required.',
            'email.email' => 'Customer email must be a valid email address.',
            'password.required' => 'Customer password is required.',
        ];
    }
}
