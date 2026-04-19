<div>
    <h1>{{ $clientId ? 'Editar Cliente' : 'Novo Cliente' }}</h1>

    <form wire:submit="save">
        <div>
            <label for="name">Nome <span aria-label="obrigatório">*</span></label>
            <input wire:model="name" id="name" type="text" required autofocus autocomplete="organization" aria-required="true"
                aria-invalid="@error('name') true @else false @enderror"
                @error('name') aria-describedby="name-error" @enderror>
            @error('name') <span id="name-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="cnpj">CNPJ</label>
            <input wire:model="cnpj" id="cnpj" type="text" placeholder="00.000.000/0000-00" autocomplete="organization-taxid"
                aria-invalid="@error('cnpj') true @else false @enderror"
                @error('cnpj') aria-describedby="cnpj-error" @enderror>
            @error('cnpj') <span id="cnpj-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="address">Endereço</label>
            <textarea wire:model="address" id="address" rows="3" autocomplete="street-address"
                aria-invalid="@error('address') true @else false @enderror"
                @error('address') aria-describedby="address-error" @enderror></textarea>
            @error('address') <span id="address-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="phone">Telefone</label>
            <input wire:model="phone" id="phone" type="tel" autocomplete="tel"
                aria-invalid="@error('phone') true @else false @enderror"
                @error('phone') aria-describedby="phone-error" @enderror>
            @error('phone') <span id="phone-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="email">E-mail</label>
            <input wire:model="email" id="email" type="email" autocomplete="email"
                aria-invalid="@error('email') true @else false @enderror"
                @error('email') aria-describedby="email-error" @enderror>
            @error('email') <span id="email-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="contact_person">Pessoa de Contato</label>
            <input wire:model="contact_person" id="contact_person" type="text" autocomplete="name"
                aria-invalid="@error('contact_person') true @else false @enderror"
                @error('contact_person') aria-describedby="contact_person-error" @enderror>
            @error('contact_person') <span id="contact_person-error">{{ $message }}</span> @enderror
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
