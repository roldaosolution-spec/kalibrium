<div>
    @if (session('success'))
        <div role="alert">{{ session('success') }}</div>
    @endif

    <div>
        <h1>Instrumentos</h1>
        @can('create', \App\Models\Instrument::class)
            <a href="{{ route('instruments.create') }}" wire:navigate>Novo Instrumento</a>
        @endcan
    </div>

    <div>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por número de série ou tipo...">
        <select wire:model.live="domainFilter">
            <option value="">Todos os domínios</option>
            @foreach ($domains as $domain)
                <option value="{{ $domain->value }}">{{ $domain->label() }}</option>
            @endforeach
        </select>
    </div>

    <table>
        <caption>Lista de instrumentos</caption>
        <thead>
            <tr>
                <th scope="col">Nº Série</th>
                <th scope="col">Tipo</th>
                <th scope="col">Domínio</th>
                <th scope="col">Faixa</th>
                <th scope="col">Cliente</th>
                <th scope="col">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($instruments as $instrument)
                <tr>
                    <td>{{ $instrument->serial_number }}</td>
                    <td>{{ $instrument->type }}</td>
                    <td>{{ $instrument->domain->label() }}</td>
                    <td>
                        @if ($instrument->range_min !== null && $instrument->range_max !== null)
                            {{ $instrument->range_min }} a {{ $instrument->range_max }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $instrument->client?->name ?? '—' }}</td>
                    <td>
                        @can('update', $instrument)
                            <a href="{{ route('instruments.edit', $instrument->id) }}" wire:navigate aria-label="Editar instrumento {{ $instrument->serial_number }}">Editar</a>
                        @endcan
                        @can('delete', $instrument)
                            <button wire:click="delete('{{ $instrument->id }}')"
                                wire:confirm="Confirma a exclusão do instrumento {{ $instrument->serial_number }}?"
                                aria-label="Excluir instrumento {{ $instrument->serial_number }}">
                                Excluir
                            </button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nenhum instrumento encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $instruments->links() }}
</div>
