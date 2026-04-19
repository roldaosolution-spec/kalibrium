<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Standard;
use App\Models\User;

class StandardPolicy
{
    private function isManager(User $user): bool
    {
        return in_array($user->role->value, Role::managerRoles(), true);
    }

    private function isGerente(User $user): bool
    {
        return $user->role === Role::Gerente;
    }

    public function viewAny(User $user): bool
    {
        return $this->isManager($user) || $user->role === Role::Tecnico;
    }

    public function view(User $user, Standard $standard): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, Standard $standard): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, Standard $standard): bool
    {
        return $this->isGerente($user);
    }
}
