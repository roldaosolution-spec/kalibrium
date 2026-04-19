<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        // Bypass TenantScope — authentication is global, isolation follows after.
        $user = User::withoutGlobalScope(TenantScope::class)
            ->where('email', $request->email)
            ->first();

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $deviceName = $request->input('device_name', 'api');
        $token = $user->createToken($deviceName, ['*'], now()->addDays(4));

        return response()->json(['token' => $token->plainTextToken]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Token revogado.']);
    }
}
