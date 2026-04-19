<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Calibration;
use App\Models\User;

class CalibrationPolicy
{
    private function isManager(User $user): bool
    {
        return in_array($user->role->value, Role::managerRoles(), true);
    }

    private function isTechnician(User $user): bool
    {
        return $user->role === Role::Tecnico;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Calibration $calibration): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        if ($this->isManager($user)) {
            return true;
        }

        return $this->isTechnician($user);
    }

    public function update(User $user, Calibration $calibration): bool
    {
        if ($this->isManager($user)) {
            return true;
        }

        return $this->isTechnician($user) && $user->id === $calibration->executor_id;
    }

    public function approve(User $user, Calibration $calibration): bool
    {
        return $this->isTechnician($user) && $user->id !== $calibration->executor_id;
    }

    public function issue(User $user, Calibration $calibration): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, Calibration $calibration): bool
    {
        return $user->role === Role::Gerente;
    }
}
