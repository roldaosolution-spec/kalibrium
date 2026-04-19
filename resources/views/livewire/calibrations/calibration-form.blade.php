<div>
    @if (session('success'))
        <div role="alert">{{ session('success') }}</div>
    @endif

    <h1>Calibração — {{ $calibration->instrument?->serial_number }}</h1>
    <p>Status: {{ $calibration->status->label() }}</p>
    <p>Padrão: {{ $calibration->standard?->serial_number ?? '—' }}</p>
    <p>Procedimento: {{ $calibration->procedure?->code ?? '—' }}</p>
    <p>Executor: {{ $calibration->executor?->name ?? '—' }}</p>

    @if ($calibration->status === \App\Enums\CalibrationStatus::Draft)
        @can('update', $calibration)
            <button wire:click="start" wire:confirm="Iniciar calibração?">Iniciar Calibração</button>
        @endcan
    @endif

    @if ($calibration->status === \App\Enums\CalibrationStatus::InProgress)
        <h2>Adicionar Ponto</h2>
        <div>
            <label for="nominalValue">Valor nominal <span aria-label="obrigatório">*</span></label>
            <input wire:model="nominalValue" id="nominalValue" type="number" step="0.000001" required
                aria-invalid="@error('nominalValue') true @else false @enderror"
                @error('nominalValue') aria-describedby="nominalValue-error" @enderror>
            @error('nominalValue') <span id="nominalValue-error">{{ $message }}</span> @enderror

            <label for="measuredValue">Valor medido <span aria-label="obrigatório">*</span></label>
            <input wire:model="measuredValue" id="measuredValue" type="number" step="0.000001" required
                aria-invalid="@error('measuredValue') true @else false @enderror"
                @error('measuredValue') aria-describedby="measuredValue-error" @enderror>
            @error('measuredValue') <span id="measuredValue-error">{{ $message }}</span> @enderror

            <label for="unit">Unidade <span aria-label="obrigatório">*</span></label>
            <input wire:model="unit" id="unit" type="text" required
                aria-invalid="@error('unit') true @else false @enderror"
                @error('unit') aria-describedby="unit-error" @enderror>
            @error('unit') <span id="unit-error">{{ $message }}</span> @enderror

            <label for="uncertainty">Incerteza <span aria-label="obrigatório">*</span></label>
            <input wire:model="uncertainty" id="uncertainty" type="number" step="0.000001" required
                aria-invalid="@error('uncertainty') true @else false @enderror"
                @error('uncertainty') aria-describedby="uncertainty-error" @enderror>
            @error('uncertainty') <span id="uncertainty-error">{{ $message }}</span> @enderror

            <button wire:click="addPoint">Adicionar Ponto</button>
        </div>

        @can('update', $calibration)
            <button wire:click="submitForReview" wire:confirm="Enviar para revisão?">
                Enviar para Revisão
            </button>
        @endcan
    @endif

    <h2>Pontos de Calibração</h2>
    <livewire:calibrations.calibration-point-grid :id="$calibration->id" />
</div>
