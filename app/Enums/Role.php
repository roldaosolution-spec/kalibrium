<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Gerente = 'gerente';
    case Tecnico = 'tecnico';
    case MotoristaUmc = 'motorista_umc';
    case Vendedor = 'vendedor';
    case Administrativo = 'administrativo';

    public function label(): string
    {
        return match ($this) {
            self::Gerente => 'Gerente',
            self::Tecnico => 'Técnico',
            self::MotoristaUmc => 'Motorista UMC',
            self::Vendedor => 'Vendedor',
            self::Administrativo => 'Administrativo',
        };
    }

    /** @return array<string> Returns roles that can manage other users. */
    public static function managerRoles(): array
    {
        return [self::Gerente->value, self::Administrativo->value];
    }
}
