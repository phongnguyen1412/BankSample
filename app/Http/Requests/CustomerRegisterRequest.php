<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRegisterRequest extends FormRequest
{
    /**
     * Rules
     *
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
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
            'name.required' => 'Customer name is required.',
            'email.required' => 'Customer email is required.',
            'email.email' => 'Customer email must be a valid email address.',
            'password.required' => 'Customer password is required.',
            'password.min' => 'Customer password must be at least 6 characters.',
        ];
    }
}
