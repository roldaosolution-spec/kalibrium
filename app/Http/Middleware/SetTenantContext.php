<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

// Infrastructure note (KAL-20 F6 / KAL-18 F-DOC-04 / KAL-67): this middleware is
// registered on both the 'web' and 'api' groups (bootstrap/app.php). Unauthenticated
// requests pass through without setting tenant context ($user === null early return).
//
// Sanctum's tokenable morphTo uses App\Models\PersonalAccessToken, which bypasses
// TenantScope so $request->user('sanctum') is safe to call here.
//
// PgBouncer must run in session (not transaction) mode — set_config() persists the GUC
// for the connection lifetime, which requires a stable connection per request (ADR-0016).
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve user via Sanctum (Bearer token) or the default session guard
        // (actingAs() in tests). PersonalAccessToken::tokenable() bypasses TenantScope,
        // so this call is safe before any tenant context is established.
        $user = $request->user('sanctum') ?? $request->user();

        if ($user === null) {
            return $next($request);
        }

        if ($user->tenant_id === null) {
            abort(401, 'Autenticação necessária com tenant válido.');
        }

        $tenantId = (string) $user->tenant_id;

        TenantContext::set($tenantId);

        // Session-level GUC via set_config so RLS reads it on every query in this
        // connection. SET LOCAL would reset immediately after the statement in
        // autocommit mode, effectively bypassing RLS.
        DB::select('SELECT set_config(?, ?, false)', [TenantContext::GUC_NAME, $tenantId]);

        try {
            return $next($request);
        } finally {
            // Clear GUC + PHP context so a pooled connection never leaks tenant.
            DB::select('SELECT set_config(?, ?, false)', [TenantContext::GUC_NAME, '']);
            TenantContext::clear();
        }
    }
}
