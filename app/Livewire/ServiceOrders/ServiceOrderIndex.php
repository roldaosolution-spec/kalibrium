<?php

declare(strict_types=1);

namespace App\Livewire\ServiceOrders;

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ServiceOrderIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $serviceOrders = ServiceOrder::query()
            ->with(['client', 'assignedTechnician'])
            ->when($this->search, fn ($q) => $q->where('number', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.service-orders.service-order-index', [
            'serviceOrders' => $serviceOrders,
            'statuses' => ServiceOrderStatus::cases(),
        ]);
    }
}
