<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    private function isManager(User $user): bool
    {
        return in_array($user->role->value, Role::managerRoles(), true);
    }

    private function isGerente(User $user): bool
    {
        return $user->role === Role::Gerente;
    }

    public function viewAny(User $authUser): bool
    {
        return $this->isManager($authUser);
    }

    public function view(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id || $this->isManager($authUser);
    }

    public function create(User $authUser): bool
    {
        return $this->isGerente($authUser);
    }

    public function update(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id || $this->isGerente($authUser);
    }

    public function delete(User $authUser, User $user): bool
    {
        return $this->isGerente($authUser) && $authUser->id !== $user->id;
    }
}
