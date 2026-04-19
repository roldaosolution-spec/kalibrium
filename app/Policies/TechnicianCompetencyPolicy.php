<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\TechnicianCompetency;
use App\Models\User;

class TechnicianCompetencyPolicy
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
        return $this->isManager($user);
    }

    public function view(User $user, TechnicianCompetency $competency): bool
    {
        if ($this->isManager($user)) {
            return true;
        }

        return $user->id === $competency->user_id;
    }

    public function create(User $user): bool
    {
        return $this->isGerente($user);
    }

    public function update(User $user, TechnicianCompetency $competency): bool
    {
        return $this->isGerente($user);
    }

    public function delete(User $user, TechnicianCompetency $competency): bool
    {
        return $this->isGerente($user);
    }
}
