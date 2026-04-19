<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\ServiceOrder;
use App\Models\User;

class ServiceOrderPolicy
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
        return $this->isManager($user) || $this->isTechnician($user);
    }

    public function view(User $user, ServiceOrder $serviceOrder): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, ServiceOrder $serviceOrder): bool
    {
        if ($this->isManager($user)) {
            return true;
        }

        return $this->isTechnician($user) && $user->id === $serviceOrder->assigned_technician_id;
    }

    public function delete(User $user, ServiceOrder $serviceOrder): bool
    {
        return $user->role === Role::Gerente;
    }

    public function transition(User $user, ServiceOrder $serviceOrder): bool
    {
        if ($this->isManager($user)) {
            return true;
        }

        return $this->isTechnician($user) && $user->id === $serviceOrder->assigned_technician_id;
    }
}
