<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CnpjRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $raw = preg_replace('/\D/', '', (string) $value);

        if ($raw === null || strlen($raw) !== 14) {
            $fail('O CNPJ deve ter 14 dígitos.');

            return;
        }

        $digits = $raw;

        if (preg_match('/^(\d)\1{13}$/', $digits)) {
            $fail('O CNPJ informado é inválido.');

            return;
        }

        if (! preg_match('/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/', (string) $value)) {
            $fail('O CNPJ deve estar no formato 00.000.000/0000-00.');

            return;
        }

        if (! $this->validCheckDigits($digits)) {
            $fail('O dígito verificador do CNPJ é inválido.');
        }
    }

    private function validCheckDigits(string $digits): bool
    {
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum1 = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum1 += (int) $digits[$i] * $weights1[$i];
        }
        $rem1 = $sum1 % 11;
        $check1 = $rem1 < 2 ? 0 : 11 - $rem1;

        $sum2 = 0;

        for ($i = 0; $i < 13; $i++) {
            $sum2 += (int) $digits[$i] * $weights2[$i];
        }
        $rem2 = $sum2 % 11;
        $check2 = $rem2 < 2 ? 0 : 11 - $rem2;

        return (int) $digits[12] === $check1 && (int) $digits[13] === $check2;
    }
}
