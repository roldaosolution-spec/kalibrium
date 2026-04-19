<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

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
