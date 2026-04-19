<?php

namespace App\Http\Middleware;

use App\Models\Scopes\TenantContext;
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

        // SET (session-level) so RLS policy applies to all subsequent queries in this
        // connection. SET LOCAL would be scoped to the implicit autocommit transaction
        // and would be lost before any real query executes.
        DB::statement('SET app.current_tenant_id = ?', [$tenantId]);

        return $next($request);
    }
}
