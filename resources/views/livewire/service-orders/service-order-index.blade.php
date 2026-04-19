<div>
    @if (session('success'))
        <div role="alert">{{ session('success') }}</div>
    @endif

    <div>
        <h1>Ordens de Serviço</h1>
        @can('create', \App\Models\ServiceOrder::class)
            <a href="{{ route('service-orders.create') }}" wire:navigate>Nova OS</a>
        @endcan
    </div>

    <div>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por número...">
        <select wire:model.live="statusFilter">
            <option value="">Todos os status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>

    <table>
        <thead>
            <tr>
                <th>Número</th>
                <th>Cliente</th>
                <th>Modo</th>
                <th>Status</th>
                <th>SLA</th>
                <th>Técnico</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($serviceOrders as $os)
                <tr>
                    <td>{{ $os->number }}</td>
                    <td>{{ $os->client?->name ?? '—' }}</td>
                    <td>{{ $os->mode->label() }}</td>
                    <td>{{ $os->status->label() }}</td>
                    <td>{{ $os->sla_date?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $os->assignedTechnician?->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route('service-orders.show', $os->id) }}" wire:navigate>Ver</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nenhuma OS encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $serviceOrders->links() }}
</div>
