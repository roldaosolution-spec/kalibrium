<?php

declare(strict_types=1);

namespace App\Enums;

enum CalibrationStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Issued = 'issued';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::InProgress => 'Em Andamento',
            self::PendingReview => 'Aguardando Revisão',
            self::Approved => 'Aprovada',
            self::Issued => 'Certificado Emitido',
            self::Cancelled => 'Cancelada',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::InProgress, self::Cancelled],
            self::InProgress => [self::PendingReview, self::Cancelled],
            self::PendingReview => [self::InProgress, self::Approved, self::Cancelled],
            self::Approved => [self::Issued, self::Cancelled],
            self::Issued => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
