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
            <input wire:model="nominalValue" type="number" step="0.000001" placeholder="Valor nominal">
            <input wire:model="measuredValue" type="number" step="0.000001" placeholder="Valor medido">
            <input wire:model="unit" type="text" placeholder="Unidade">
            <input wire:model="uncertainty" type="number" step="0.000001" placeholder="Incerteza">
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
