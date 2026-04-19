<div>
    @if (session('success'))
        <div role="alert">{{ session('success') }}</div>
    @endif

    <div>
        <h1>Clientes</h1>
        @can('create', \App\Models\Client::class)
            <a href="{{ route('clients.create') }}" wire:navigate>Novo Cliente</a>
        @endcan
    </div>

    <div>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nome ou CNPJ...">
    </div>

    <table>
        <caption>Lista de clientes</caption>
        <thead>
            <tr>
                <th scope="col">Nome</th>
                <th scope="col">CNPJ</th>
                <th scope="col">Contato</th>
                <th scope="col">Telefone</th>
                <th scope="col">E-mail</th>
                <th scope="col">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->cnpj ?? '—' }}</td>
                    <td>{{ $client->contact_person ?? '—' }}</td>
                    <td>{{ $client->phone ?? '—' }}</td>
                    <td>{{ $client->email ?? '—' }}</td>
                    <td>
                        @can('update', $client)
                            <a href="{{ route('clients.edit', $client->id) }}" wire:navigate aria-label="Editar cliente {{ $client->name }}">Editar</a>
                        @endcan
                        @can('delete', $client)
                            <button wire:click="delete('{{ $client->id }}')"
                                wire:confirm="Confirma a exclusão do cliente {{ $client->name }}?"
                                aria-label="Excluir cliente {{ $client->name }}">
                                Excluir
                            </button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Nenhum cliente encontrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $clients->links() }}
</div>
