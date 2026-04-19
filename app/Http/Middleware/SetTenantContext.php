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

        // Configura RLS no PostgreSQL para a conexão atual
        DB::statement('SET LOCAL app.current_tenant_id = ?', [$tenantId]);

        return $next($request);
    }
}
