<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Models\Client;
use App\Rules\CnpjRule;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ClientForm extends Component
{
    #[Locked]
    public ?string $clientId = null;

    public string $name = '';
    public string $cnpj = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $contact_person = '';

    public function mount(?string $id = null): void
    {
        if ($id !== null) {
            $client = Client::findOrFail($id);
            $this->authorize('update', $client);
            $this->clientId = $client->id;
            $this->name = $client->name;
            $this->cnpj = $client->cnpj ?? '';
            $this->address = $client->address ?? '';
            $this->phone = $client->phone ?? '';
            $this->email = $client->email ?? '';
            $this->contact_person = $client->contact_person ?? '';
        } else {
            $this->authorize('create', Client::class);
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', new CnpjRule],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->clientId !== null) {
            $client = Client::findOrFail($this->clientId);
            $this->authorize('update', $client);
            $client->update($data);
            session()->flash('success', 'Cliente atualizado com sucesso.');
        } else {
            $this->authorize('create', Client::class);
            Client::create($data);
            session()->flash('success', 'Cliente criado com sucesso.');
        }

        $this->redirect(route('clients.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.clients.client-form');
    }
}
