<?php

declare(strict_types=1);

namespace App\Livewire\ServiceOrders;

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use Illuminate\View\View;
use Livewire\Component;

class ServiceOrderDetail extends Component
{
    public ServiceOrder $serviceOrder;

    public function mount(string $id): void
    {
        $this->serviceOrder = ServiceOrder::with(['client', 'assignedTechnician', 'calibrations.instrument'])->findOrFail($id);
    }

    public function advance(string $newStatus): void
    {
        $this->authorize('transition', $this->serviceOrder);

        $status = ServiceOrderStatus::from($newStatus);
        $this->serviceOrder->changeStatus($status);
        $this->serviceOrder->refresh();

        session()->flash('success', "OS atualizada para: {$status->label()}");
    }

    public function render(): View
    {
        return view('livewire.service-orders.service-order-detail', [
            'serviceOrder' => $this->serviceOrder,
        ]);
    }
}
