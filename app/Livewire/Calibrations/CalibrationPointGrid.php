<?php

declare(strict_types=1);

namespace App\Livewire\Calibrations;

use App\Models\Calibration;
use App\Models\CalibrationPoint;
use Illuminate\View\View;
use Livewire\Component;

class CalibrationPointGrid extends Component
{
    public Calibration $calibration;

    public function mount(string $id): void
    {
        $this->calibration = Calibration::with('points')->findOrFail($id);
    }

    public function deletePoint(string $pointId): void
    {
        $this->authorize('update', $this->calibration);
        CalibrationPoint::findOrFail($pointId)->delete();
        $this->calibration->load('points');
    }

    public function render(): View
    {
        return view('livewire.calibrations.calibration-point-grid', [
            'calibration' => $this->calibration,
            'points' => $this->calibration->points,
        ]);
    }
}
