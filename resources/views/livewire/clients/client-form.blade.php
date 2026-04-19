<div>
    <h1>{{ $clientId ? 'Editar Cliente' : 'Novo Cliente' }}</h1>

    <form wire:submit="save">
        <div>
            <label for="name">Nome *</label>
            <input wire:model="name" id="name" type="text" required autofocus>
            @error('name') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="cnpj">CNPJ</label>
            <input wire:model="cnpj" id="cnpj" type="text" placeholder="00.000.000/0000-00">
            @error('cnpj') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="address">Endereço</label>
            <textarea wire:model="address" id="address" rows="3"></textarea>
            @error('address') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="phone">Telefone</label>
            <input wire:model="phone" id="phone" type="tel">
            @error('phone') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="email">E-mail</label>
            <input wire:model="email" id="email" type="email">
            @error('email') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="contact_person">Pessoa de Contato</label>
            <input wire:model="contact_person" id="contact_person" type="text">
            @error('contact_person') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $clientId ? 'Atualizar' : 'Criar' }} Cliente</span>
                <span wire:loading>Salvando...</span>
            </button>
            <a href="{{ route('clients.index') }}" wire:navigate>Cancelar</a>
        </div>
    </form>
</div>
