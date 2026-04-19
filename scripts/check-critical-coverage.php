#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Verifica cobertura mínima nos domínios críticos definidos em ADR-0017.
 * Executado no CI após pest --coverage-clover para garantir que domínios
 * críticos atingem 100% de cobertura, não apenas o piso global de 80%.
 *
 * Uso: php scripts/check-critical-coverage.php coverage/clover.xml
 */
$coverageFile = $argv[1] ?? 'coverage/clover.xml';

if (! file_exists($coverageFile)) {
    echo "Arquivo de cobertura não encontrado: {$coverageFile}\n";
    echo "Execute: ./vendor/bin/pest --coverage-clover=coverage/clover.xml\n";
    exit(1);
}

$xml = simplexml_load_file($coverageFile);

if ($xml === false) {
    echo "Erro ao parsear o arquivo de cobertura XML.\n";
    exit(1);
}

/**
 * Domínios críticos e cobertura mínima exigida (ADR-0017).
 * Chave: fragmento de caminho do arquivo. Valor: cobertura mínima em %.
 */
$criticalDomains = [
    'Models/Scopes/TenantScope.php' => 100,
    'Models/Concerns/HasTenant.php' => 100,
    'Http/Middleware/SetTenantContext.php' => 100,
];

$failures = [];

foreach ($criticalDomains as $pathFragment => $minCoverage) {
    $covered = findFileCoverage($xml, $pathFragment);

    if ($covered === null) {
        $failures[] = "AUSENTE: {$pathFragment} — não encontrada na cobertura (mínimo: {$minCoverage}%)";
        echo "[AUSENTE] {$pathFragment}: não encontrada\n";
        continue;
    }

    $pass = $covered >= $minCoverage;
    $status = $pass ? 'PASS' : 'FAIL';
    echo "[{$status}] {$pathFragment}: {$covered}% (mínimo: {$minCoverage}%)\n";

    if (! $pass) {
        $failures[] = "FAIL: {$pathFragment} — cobertura {$covered}% < mínimo {$minCoverage}%";
    }
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

function findFileCoverage(SimpleXMLElement $xml, string $pathFragment): ?float
{
    // PHPUnit Clover XML nests files inside <package> elements (one per namespace).
    // Search both top-level <file> nodes and those inside <package> children.
    $candidates = array_merge(
        iterator_to_array($xml->project->file ?? [], false),
        ...array_map(
            fn ($pkg) => iterator_to_array($pkg->file ?? [], false),
            iterator_to_array($xml->project->package ?? [], false),
        ),
    );

    foreach ($candidates as $file) {
        $name = (string) $file['name'];

        if (! str_contains($name, $pathFragment)) {
            continue;
        }

        $metrics = $file->metrics;

        if ($metrics === null) {
            return null;
        }

        $total = (int) $metrics['statements'];
        $covered = (int) $metrics['coveredstatements'];

        if ($total === 0) {
            return 100.0;
        }

        return round(($covered / $total) * 100, 2);
    }

    return null;
}
