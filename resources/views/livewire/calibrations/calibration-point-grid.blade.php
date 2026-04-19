<div>
    <table>
        <caption>Pontos de calibração</caption>
        <thead>
            <tr>
                <th scope="col">Nominal</th>
                <th scope="col">Medido</th>
                <th scope="col">Unidade</th>
                <th scope="col">Desvio</th>
                <th scope="col">Incerteza</th>
                <th scope="col">Resultado</th>
                <th scope="col">Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($points as $point)
                <tr>
                    <td>{{ $point->nominal_value }}</td>
                    <td>{{ $point->measured_value }}</td>
                    <td>{{ $point->unit }}</td>
                    <td>{{ $point->deviation }}</td>
                    <td>{{ $point->uncertainty }}</td>
                    <td>{{ $point->pass ? 'CONFORME' : 'NÃO CONFORME' }}</td>
                    <td>
                        @can('update', $calibration)
                            <button wire:click="deletePoint('{{ $point->id }}')"
                                wire:confirm="Remover este ponto?"
                                aria-label="Remover ponto de calibração nominal {{ $point->nominal_value }}">
                                Remover
                            </button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nenhum ponto registrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
