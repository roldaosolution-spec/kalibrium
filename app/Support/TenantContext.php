<?php

namespace App\Support;

/**
 * Request-scoped tenant context for application-layer isolation.
 * Stores the active tenant ID set by SetTenantContext middleware.
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
