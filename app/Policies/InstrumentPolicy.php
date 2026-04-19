<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Instrument;
use App\Models\User;

class InstrumentPolicy
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
        return true;
    }

    public function view(User $user, Instrument $instrument): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, Instrument $instrument): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, Instrument $instrument): bool
    {
        return $this->isGerente($user);
    }
}
