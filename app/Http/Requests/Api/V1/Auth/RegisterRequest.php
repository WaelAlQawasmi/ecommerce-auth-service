<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Enums\RoleSlug;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[BodyParameter('name', description: 'Display name of the new user.', example: 'Jane Doe')]
#[BodyParameter('email', description: 'Unique email address (stored lowercase).', example: 'jane@example.com')]
#[BodyParameter('password', description: 'Password — minimum 8 characters.', example: 'Secret123!')]
#[BodyParameter('password_confirmation', description: 'Must match `password`.', example: 'Secret123!')]
#[BodyParameter(
    'role',
    description: 'Optional role slug. Defaults to `customer`. Assigning staff roles (`admin`, `support`) requires an authenticated admin Bearer token.',
    example: 'customer',
)]
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->input('role', RoleSlug::Customer->value);

        if ($role === RoleSlug::Customer->value) {
            return true;
        }

        return $this->user()?->hasRole(RoleSlug::Admin->value) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['sometimes', 'string', Rule::exists('roles', 'slug')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower((string) $this->input('email'))]);
        }

        if (! $this->has('role')) {
            $this->merge(['role' => RoleSlug::Customer->value]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $role = $this->input('role');

            if (! is_string($role) || $role === RoleSlug::Customer->value) {
                return;
            }

            if (! $this->user()?->hasRole(RoleSlug::Admin->value)) {
                $validator->errors()->add(
                    'role',
                    'Only administrators can create users with non-customer roles.',
                );
            }
        });
    }
}
