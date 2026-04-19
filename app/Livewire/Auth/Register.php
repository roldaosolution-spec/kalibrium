<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Enums\Role;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $tenant_id = '';
    public string $role = Role::Administrativo->value;

    public function register(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'confirmed', 'min:8'],
            'tenant_id' => ['required', 'uuid', 'exists:tenants,id'],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        TenantContext::set($this->tenant_id);

        try {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'tenant_id' => $this->tenant_id,
            ]);
        } finally {
            TenantContext::clear();
        }

        Auth::login($user);
        session()->regenerate();
        $this->redirect(route('home'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
