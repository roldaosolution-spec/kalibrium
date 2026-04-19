<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

// Infrastructure note (KAL-20 F6 / KAL-18 F-DOC-04): this middleware is registered
// only on the 'api' group (bootstrap/app.php). Web routes must NOT access Eloquent
// models that carry HasTenant — TenantScope will throw RuntimeException without context.
// If web routes ever need tenant-scoped data, add this middleware to the 'web' group.
//
// PgBouncer must run in session (not transaction) mode — set_config() persists the GUC
// for the connection lifetime, which requires a stable connection per request (ADR-0016).
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->tenant_id === null) {
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
