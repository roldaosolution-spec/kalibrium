<?php

declare(strict_types=1);

use App\Rules\CnpjRule;

describe('CnpjRule', function (): void {
    function validateCnpj(string $value): bool
    {
        $rule = new CnpjRule();
        $passed = true;
        $rule->validate('cnpj', $value, function () use (&$passed): void {
            $passed = false;
        });
        return $passed;
    }

    it('aceita CNPJ valido', function (): void {
        expect(validateCnpj('11.222.333/0001-81'))->toBeTrue();
        expect(validateCnpj('99.999.999/0001-91'))->toBeTrue();
    });

    it('rejeita CNPJ com todos os digitos iguais', function (): void {
        expect(validateCnpj('11.111.111/1111-11'))->toBeFalse();
        expect(validateCnpj('00.000.000/0000-00'))->toBeFalse();
    });

    it('rejeita CNPJ sem pontuacao', function (): void {
        expect(validateCnpj('11222333000181'))->toBeFalse();
    });

    it('rejeita CNPJ com formato incorreto', function (): void {
        expect(validateCnpj('11/222.333.0001-81'))->toBeFalse();
        expect(validateCnpj('11.222.333-0001/81'))->toBeFalse();
    });

    it('rejeita CNPJ com digito verificador errado', function (): void {
        expect(validateCnpj('11.222.333/0001-99'))->toBeFalse();
        expect(validateCnpj('11.222.333/0001-80'))->toBeFalse();
    });

    it('rejeita CNPJ com menos de 14 digitos', function (): void {
        expect(validateCnpj('11.222.333/0001-8'))->toBeFalse();
    });
});
