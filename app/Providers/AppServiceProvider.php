<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\TenantAgnosticUserProvider;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void {}

    public function boot(): void
    {
        // Register the tenant-agnostic auth provider so authentication bypasses
        // TenantScope (emails are globally unique — isolation is enforced post-auth).
        Auth::provider('tenant-agnostic', fn (Application $app, array $config): TenantAgnosticUserProvider => new TenantAgnosticUserProvider($app['hash'], $config['model']));

        Gate::policy(User::class, UserPolicy::class);

        // Use custom PersonalAccessToken that bypasses TenantScope when resolving
        // the tokenable owner during Sanctum authentication.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
