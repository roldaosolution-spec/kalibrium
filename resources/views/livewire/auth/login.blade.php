<div>
    <form wire:submit="authenticate">
        <div>
            <label for="email">E-mail</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" autofocus>
            @error('email') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="password">Senha</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password">
            @error('password') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label>
                <input wire:model="remember" type="checkbox"> Lembrar-me
            </label>
        </div>
        <button type="submit" wire:loading.attr="disabled">
            <span wire:loading.remove>Entrar</span>
            <span wire:loading>Aguarde...</span>
        </button>
    </form>
    <a href="/register">Criar conta</a>
</div>
