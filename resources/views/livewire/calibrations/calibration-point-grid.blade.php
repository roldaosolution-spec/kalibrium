<div>
    <table>
        <thead>
            <tr>
                <th>Nominal</th>
                <th>Medido</th>
                <th>Unidade</th>
                <th>Desvio</th>
                <th>Incerteza</th>
                <th>Resultado</th>
                <th>Ações</th>
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
                                wire:confirm="Remover este ponto?">
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
