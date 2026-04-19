<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    private function roleValue(User $user): string
    {
        return $user->role->value;
    }

    public function viewAny(User $authUser): bool
    {
        return in_array($this->roleValue($authUser), Role::managerRoles(), true);
    }

    public function view(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id
            || in_array($this->roleValue($authUser), Role::managerRoles(), true);
    }

    public function create(User $authUser): bool
    {
        return $this->roleValue($authUser) === Role::Gerente->value;
    }

    public function update(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id
            || $this->roleValue($authUser) === Role::Gerente->value;
    }

    public function delete(User $authUser, User $user): bool
    {
        return $this->roleValue($authUser) === Role::Gerente->value
            && $authUser->id !== $user->id;
    }
}
