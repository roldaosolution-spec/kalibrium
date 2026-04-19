<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait GeneratesAnnualSequenceNumber
{
    protected static function generateAnnualSequenceNumber(string $prefix, string $column, ?string $tenantId = null): string
    {
        $year = (int) date('Y');
        $query = static::withoutGlobalScopes()
            ->select($column)
            ->whereBetween('created_at', ["{$year}-01-01", ($year + 1) . '-01-01'])
            ->orderByDesc($column)
            ->lockForUpdate();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $last = $query->first()?->{$column};
        $seq = $last !== null ? ((int) substr($last, -4)) + 1 : 1;

        return "{$prefix}{$year}-" . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
