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
        <input wire:model="tenant_id" type="hidden" id="tenant_id">
        <input wire:model="role" type="hidden" id="role">
        <button type="submit">Cadastrar</button>
    </form>
    <a href="/login">Já tenho conta</a>
</div>
