<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Models\Client;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ClientIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(string $id): void
    {
        $client = Client::findOrFail($id);
        $this->authorize('delete', $client);
        $client->delete();
        session()->flash('success', 'Cliente excluído com sucesso.');
    }

    public function render(): View
    {
        $clients = Client::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('cnpj', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.clients.client-index', ['clients' => $clients]);
    }
}
