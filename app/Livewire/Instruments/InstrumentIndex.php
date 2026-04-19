<?php

declare(strict_types=1);

namespace App\Livewire\Instruments;

use App\Enums\Domain;
use App\Models\Instrument;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class InstrumentIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $domainFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDomainFilter(): void
    {
        $this->resetPage();
    }

    public function delete(string $id): void
    {
        $instrument = Instrument::findOrFail($id);
        $this->authorize('delete', $instrument);
        $instrument->delete();
        session()->flash('success', 'Instrumento excluído com sucesso.');
    }

    public function render(): View
    {
        $instruments = Instrument::query()
            ->with('client')
            ->when($this->search, fn ($q) => $q->where('serial_number', 'like', "%{$this->search}%")
                ->orWhere('type', 'like', "%{$this->search}%"))
            ->when($this->domainFilter, fn ($q) => $q->where('domain', $this->domainFilter))
            ->orderBy('serial_number')
            ->paginate(15);

        return view('livewire.instruments.instrument-index', [
            'instruments' => $instruments,
            'domains' => Domain::cases(),
        ]);
    }
}
