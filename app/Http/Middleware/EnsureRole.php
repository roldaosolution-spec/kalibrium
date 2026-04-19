<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $userRole = $user?->role?->value;

        if ($user === null || $userRole === null || ! in_array($userRole, $roles, true)) {
            abort(403, 'Acesso não autorizado para este perfil.');
        }

        return $next($request);
    }
}
