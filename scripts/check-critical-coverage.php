#!/usr/bin/env php
<?php

/**
 * Verifica cobertura mínima nos domínios críticos definidos em ADR-0017.
 * Executado no CI após pest --coverage para garantir que domínios críticos
 * atingem 100% de cobertura, não apenas o piso global de 80%.
 *
 * Uso: php scripts/check-critical-coverage.php coverage/coverage.json
 */

$coverageFile = $argv[1] ?? 'coverage/coverage.json';

if (! file_exists($coverageFile)) {
    echo "Arquivo de cobertura não encontrado: {$coverageFile}\n";
    echo "Execute: ./vendor/bin/pest --coverage-json=coverage/coverage.json\n";
    exit(1);
}

$data = json_decode(file_get_contents($coverageFile), true);

if ($data === null) {
    echo "Erro ao parsear o arquivo de cobertura.\n";
    exit(1);
}

/**
 * Domínios críticos e cobertura mínima exigida (ADR-0017).
 * Chave: padrão de namespace/caminho. Valor: cobertura mínima em %.
 */
$criticalDomains = [
    'App\\Models\\Scopes\\TenantScope'       => 100,
    'App\\Models\\Concerns\\HasTenant'       => 100,
    'App\\Http\\Middleware\\SetTenantContext' => 100,
];

$failures = [];
$summary = [];

foreach ($criticalDomains as $class => $minCoverage) {
    $covered = findClassCoverage($data, $class);

    if ($covered === null) {
        $failures[] = "AUSENTE: {$class} — não encontrada na cobertura (cobertura mínima: {$minCoverage}%)";
        $summary[] = ['class' => $class, 'coverage' => 0, 'min' => $minCoverage, 'pass' => false];
        continue;
    }

    $pass = $covered >= $minCoverage;
    $status = $pass ? 'PASS' : 'FAIL';
    $summary[] = ['class' => $class, 'coverage' => $covered, 'min' => $minCoverage, 'pass' => $pass];

    if (! $pass) {
        $failures[] = "FAIL: {$class} — cobertura {$covered}% < mínimo {$minCoverage}%";
    }

    echo "[{$status}] {$class}: {$covered}% (mínimo: {$minCoverage}%)\n";
}

if (count($failures) > 0) {
    echo "\n=== FALHAS DE COBERTURA CRÍTICA ===\n";

    foreach ($failures as $failure) {
        echo "  {$failure}\n";
    }

    echo "\nDomínios críticos não atingiram a cobertura mínima. Build bloqueado.\n";
    exit(1);
}

echo "\nTodos os domínios críticos atingiram a cobertura mínima exigida.\n";
exit(0);

function findClassCoverage(array $data, string $className): ?float
{
    foreach ($data['files'] ?? [] as $file => $fileData) {
        $normalizedClass = str_replace(['App/', 'app/'], '', $file);
        $normalizedClass = str_replace('/', '\\', $normalizedClass);
        $normalizedClass = preg_replace('/\.php$/', '', $normalizedClass);
        $normalizedClass = 'App\\'.$normalizedClass;

        if (str_contains($normalizedClass, str_replace('App\\', '', $className))) {
            $covered = $fileData['summary']['coveredLines'] ?? 0;
            $total = $fileData['summary']['executableLines'] ?? 0;

            if ($total === 0) {
                return 100.0;
            }

            return round(($covered / $total) * 100, 2);
        }
    }

    return null;
}
