<?php

declare(strict_types=1);

namespace App\Livewire\Instruments;

use App\Enums\Domain;
use App\Models\Client;
use App\Models\Instrument;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class InstrumentForm extends Component
{
    #[Locked]
    public ?string $instrumentId = null;

    public string $serial_number = '';
    public string $type = '';
    public string $description = '';
    public string $range_min = '';
    public string $range_max = '';
    public string $resolution = '';
    public string $domain = '';
    public ?string $client_id = null;

    public function mount(?string $id = null): void
    {
        if ($id !== null) {
            $instrument = Instrument::findOrFail($id);
            $this->authorize('update', $instrument);
            $this->instrumentId = $instrument->id;
            $this->serial_number = $instrument->serial_number;
            $this->type = $instrument->type;
            $this->description = $instrument->description ?? '';
            $this->range_min = (string) ($instrument->range_min ?? '');
            $this->range_max = (string) ($instrument->range_max ?? '');
            $this->resolution = (string) ($instrument->resolution ?? '');
            $this->domain = $instrument->domain->value;
            $this->client_id = $instrument->client_id;
        } else {
            $this->authorize('create', Instrument::class);
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'serial_number' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'range_min' => ['nullable', 'numeric'],
            'range_max' => ['nullable', 'numeric', 'gte:range_min'],
            'resolution' => ['nullable', 'numeric', 'min:0'],
            'domain' => ['required', 'string', 'in:' . implode(',', Domain::values())],
            'client_id' => ['nullable', 'uuid', 'exists:clients,id'],
        ]);

        if ($this->instrumentId !== null) {
            $instrument = Instrument::findOrFail($this->instrumentId);
            $this->authorize('update', $instrument);
            $instrument->update($data);
            session()->flash('success', 'Instrumento atualizado com sucesso.');
        } else {
            $this->authorize('create', Instrument::class);
            Instrument::create($data);
            session()->flash('success', 'Instrumento criado com sucesso.');
        }

        $this->redirect(route('instruments.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.instruments.instrument-form', [
            'domains' => Domain::cases(),
            'clients' => Client::orderBy('name')->get(),
        ]);
    }
}
