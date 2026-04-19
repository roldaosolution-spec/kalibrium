<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceOrderStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case InProgress = 'in_progress';
    case PendingReview = 'pending_review';
    case Completed = 'completed';
    case Invoiced = 'invoiced';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Open => 'Aberta',
            self::InProgress => 'Em Andamento',
            self::PendingReview => 'Aguardando Revisão',
            self::Completed => 'Concluída',
            self::Invoiced => 'Faturada',
            self::Closed => 'Encerrada',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Open],
            self::Open => [self::InProgress],
            self::InProgress => [self::PendingReview],
            self::PendingReview => [self::InProgress, self::Completed],
            self::Completed => [self::Invoiced],
            self::Invoiced => [self::Closed],
            self::Closed => [],
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
