<div>
    <h1>{{ $instrumentId ? 'Editar Instrumento' : 'Novo Instrumento' }}</h1>

    <form wire:submit="save">
        <div>
            <label for="serial_number">Número de Série <span aria-label="obrigatório">*</span></label>
            <input wire:model="serial_number" id="serial_number" type="text" required autofocus
                aria-invalid="@error('serial_number') true @else false @enderror"
                @error('serial_number') aria-describedby="serial_number-error" @enderror>
            @error('serial_number') <span id="serial_number-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="type">Tipo <span aria-label="obrigatório">*</span></label>
            <input wire:model="type" id="type" type="text" required
                aria-invalid="@error('type') true @else false @enderror"
                @error('type') aria-describedby="type-error" @enderror>
            @error('type') <span id="type-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="domain">Domínio <span aria-label="obrigatório">*</span></label>
            <select wire:model="domain" id="domain" required
                aria-invalid="@error('domain') true @else false @enderror"
                @error('domain') aria-describedby="domain-error" @enderror>
                <option value="">Selecione...</option>
                @foreach ($domains as $d)
                    <option value="{{ $d->value }}">{{ $d->label() }}</option>
                @endforeach
            </select>
            @error('domain') <span id="domain-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="description">Descrição</label>
            <textarea wire:model="description" id="description" rows="2"
                aria-invalid="@error('description') true @else false @enderror"
                @error('description') aria-describedby="description-error" @enderror></textarea>
            @error('description') <span id="description-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="range_min">Faixa Mínima</label>
            <input wire:model="range_min" id="range_min" type="number" step="any"
                aria-invalid="@error('range_min') true @else false @enderror"
                @error('range_min') aria-describedby="range_min-error" @enderror>
            @error('range_min') <span id="range_min-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="range_max">Faixa Máxima</label>
            <input wire:model="range_max" id="range_max" type="number" step="any"
                aria-invalid="@error('range_max') true @else false @enderror"
                @error('range_max') aria-describedby="range_max-error" @enderror>
            @error('range_max') <span id="range_max-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="resolution">Resolução</label>
            <input wire:model="resolution" id="resolution" type="number" step="any" min="0"
                aria-invalid="@error('resolution') true @else false @enderror"
                @error('resolution') aria-describedby="resolution-error" @enderror>
            @error('resolution') <span id="resolution-error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="client_id">Cliente</label>
            <select wire:model="client_id" id="client_id"
                aria-invalid="@error('client_id') true @else false @enderror"
                @error('client_id') aria-describedby="client_id-error" @enderror>
                <option value="">Sem cliente</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
            @error('client_id') <span id="client_id-error">{{ $message }}</span> @enderror
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
