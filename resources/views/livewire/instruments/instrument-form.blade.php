<div>
    <h1>{{ $instrumentId ? 'Editar Instrumento' : 'Novo Instrumento' }}</h1>

    <form wire:submit="save">
        <div>
            <label for="serial_number">Número de Série *</label>
            <input wire:model="serial_number" id="serial_number" type="text" required autofocus>
            @error('serial_number') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="type">Tipo *</label>
            <input wire:model="type" id="type" type="text" required>
            @error('type') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="domain">Domínio *</label>
            <select wire:model="domain" id="domain" required>
                <option value="">Selecione...</option>
                @foreach ($domains as $d)
                    <option value="{{ $d->value }}">{{ $d->label() }}</option>
                @endforeach
            </select>
            @error('domain') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="description">Descrição</label>
            <textarea wire:model="description" id="description" rows="2"></textarea>
            @error('description') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="range_min">Faixa Mínima</label>
            <input wire:model="range_min" id="range_min" type="number" step="any">
            @error('range_min') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="range_max">Faixa Máxima</label>
            <input wire:model="range_max" id="range_max" type="number" step="any">
            @error('range_max') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="resolution">Resolução</label>
            <input wire:model="resolution" id="resolution" type="number" step="any" min="0">
            @error('resolution') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="client_id">Cliente</label>
            <select wire:model="client_id" id="client_id">
                <option value="">Sem cliente</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
            @error('client_id') <span>{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $instrumentId ? 'Atualizar' : 'Criar' }} Instrumento</span>
                <span wire:loading>Salvando...</span>
            </button>
            <a href="{{ route('instruments.index') }}" wire:navigate>Cancelar</a>
        </div>
    </form>
</div>
