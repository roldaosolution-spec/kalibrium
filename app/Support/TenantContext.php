<?php

namespace App\Support;

/**
 * Request-scoped in-process tenant context (ADR-0016 Layer 1).
 *
 * Holds the active tenant ID for the current PHP request. Set by
 * SetTenantContext middleware; consumed by TenantScope and HasTenant.
 * Cleared in the middleware finally block to prevent connection-pool leakage.
 * GUC_NAME is the PostgreSQL session variable used by the RLS policy (Layer 3).
 */
class TenantContext
{
    public const GUC_NAME = 'app.current_tenant_id';

    private static ?string $tenantId = null;

    public static function set(string $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function getId(): ?string
    {
        return self::$tenantId;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
    }
}
