<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\RoleSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRoleRequest extends FormRequest
{
    /**
     * Authorization is enforced by middleware and {@see \App\Policies\UserPolicy::assignRole}.
     */
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
            'role' => ['required', 'string', Rule::exists('roles', 'slug')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $role = $this->input('role');

            if (! is_string($role) || $role === RoleSlug::Customer->value) {
                return;
            }

            $user = $this->user();

            if ($user === null || ! $user->hasRole(RoleSlug::Admin->value)) {
                $validator->errors()->add(
                    'role',
                    'Only administrators can assign non-customer roles.',
                );
            }
        });
    }
}
