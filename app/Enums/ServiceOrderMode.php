<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceOrderMode: string
{
    case Bench = 'bench';
    case Field = 'field';
    case Umc = 'umc';

    public function label(): string
    {
        return match ($this) {
            self::Bench => 'Bancada',
            self::Field => 'Campo',
            self::Umc => 'UMC',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
