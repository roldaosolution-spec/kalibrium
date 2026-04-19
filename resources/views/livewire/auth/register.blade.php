<div>
    <form wire:submit="register">
        <div>
            <label for="name">Nome</label>
            <input wire:model="name" id="name" type="text" autocomplete="name" autofocus>
            @error('name') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="email">E-mail</label>
            <input wire:model="email" id="email" type="email" autocomplete="email">
            @error('email') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="password">Senha</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password">
            @error('password') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="password_confirmation">Confirmar senha</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password">
        </div>
        @error('tenant_id') <span>{{ $message }}</span> @enderror
        <button type="submit" wire:loading.attr="disabled">
            <span wire:loading.remove>Cadastrar</span>
            <span wire:loading>Aguarde...</span>
        </button>
    </form>
    <a href="/login">Já tenho conta</a>
</div>
