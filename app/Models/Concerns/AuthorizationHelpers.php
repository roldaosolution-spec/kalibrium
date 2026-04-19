<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\CalibrationStatus;
use App\Enums\Domain;
use App\Enums\ServiceOrderStatus;
use App\Exceptions\InvalidTransitionException;
use App\Models\TechnicianCompetency;
use App\Models\User;

trait AuthorizationHelpers
{
    protected function assertTransitionAllowed(CalibrationStatus|ServiceOrderStatus $newStatus): void
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            throw new InvalidTransitionException(
                "Transição inválida: {$this->status->value} → {$newStatus->value}",
            );
        }
    }

    protected function assertTechnicianCompetency(User $user, Domain $domain): void
    {
        $hasCompetency = TechnicianCompetency::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant_id)
            ->where('user_id', $user->id)
            ->where('domain', $domain->value)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();

        if (! $hasCompetency) {
            throw new InvalidTransitionException(
                "Técnico não possui competência válida para o domínio: {$domain->label()}",
            );
        }
    }
}
