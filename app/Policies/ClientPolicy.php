<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Role;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
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

    public function view(User $user, Client $client): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isManager($user);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->isManager($user);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->isGerente($user);
    }
}
