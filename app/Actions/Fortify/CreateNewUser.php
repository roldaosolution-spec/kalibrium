<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Enums\Role;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users'),
            ],
            'password' => $this->passwordRules(),
            'tenant_id' => ['required', 'uuid', 'exists:tenants,id'],
            'role' => ['required', Rule::enum(Role::class)],
        ])->validate();

        // HasTenant::creating() needs TenantContext set when tenant_id is not in fillable
        // (or when creating via context rather than explicit field).
        TenantContext::set($input['tenant_id']);

        try {
            return User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'role' => $input['role'],
                'tenant_id' => $input['tenant_id'],
            ]);
        } finally {
            TenantContext::clear();
        }
    }
}
