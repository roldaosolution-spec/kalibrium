<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorSetup
{
    /** @var array<string> */
    private const array ROLES_REQUIRING_2FA = ['gerente', 'administrativo'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $role = $user->role->value;

        if (! in_array($role, self::ROLES_REQUIRING_2FA, true)) {
            return $next($request);
        }

        if ($user->two_factor_confirmed_at !== null) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Autenticação de dois fatores obrigatória para este perfil.');
        }

        return redirect()->route('two-factor.setup');
    }
}
