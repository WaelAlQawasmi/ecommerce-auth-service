<?php

namespace App\Http\Requests\Api\V1\Auth;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

#[BodyParameter('email', description: 'Registered account email.', example: 'jane@example.com')]
#[BodyParameter('password', description: 'Account password.', example: 'Secret123!')]
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower((string) $this->input('email'))]);
        }
    }
}
