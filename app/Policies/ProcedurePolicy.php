<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Procedure;
use App\Models\User;

class ProcedurePolicy
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

    public function view(User $user, Procedure $procedure): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isGerente($user);
    }

    public function update(User $user, Procedure $procedure): bool
    {
        return $this->isGerente($user);
    }

    public function delete(User $user, Procedure $procedure): bool
    {
        return $this->isGerente($user);
    }
}
