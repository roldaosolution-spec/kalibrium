<div>
    @if (session('success'))
        <div role="alert">{{ session('success') }}</div>
    @endif

    <h1>OS {{ $serviceOrder->number }}</h1>
    <p>Status: {{ $serviceOrder->status->label() }}</p>
    <p>Cliente: {{ $serviceOrder->client?->name ?? '—' }}</p>
    <p>Modo: {{ $serviceOrder->mode->label() }}</p>
    <p>SLA: {{ $serviceOrder->sla_date?->format('d/m/Y') ?? '—' }}</p>
    <p>Técnico: {{ $serviceOrder->assignedTechnician?->name ?? '—' }}</p>
    @if ($serviceOrder->notes)
        <p>Observações: {{ $serviceOrder->notes }}</p>
    @endif

    @can('transition', $serviceOrder)
        <div>
            @foreach ($serviceOrder->status->allowedTransitions() as $nextStatus)
                <button wire:click="advance('{{ $nextStatus->value }}')"
                    wire:confirm="Confirmar transição para: {{ $nextStatus->label() }}?">
                    {{ $nextStatus->label() }}
                </button>
            @endforeach
        </div>
    @endcan

    <h2>Calibrações</h2>
    <table>
        <caption>Calibrações da ordem de serviço</caption>
        <thead>
            <tr>
                <th scope="col">Instrumento</th>
                <th scope="col">Status</th>
                <th scope="col">Certificado</th>
                <th scope="col">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($serviceOrder->calibrations as $calibration)
                <tr>
                    <td>{{ $calibration->instrument?->serial_number ?? '—' }}</td>
                    <td>{{ $calibration->status->label() }}</td>
                    <td>{{ $calibration->certificate_number ?? '—' }}</td>
                    <td>
                        <a href="{{ route('calibrations.show', $calibration->id) }}" wire:navigate aria-label="Ver calibração do instrumento {{ $calibration->instrument?->serial_number ?? 'desconhecido' }}">Ver</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Nenhuma calibração nesta OS.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
