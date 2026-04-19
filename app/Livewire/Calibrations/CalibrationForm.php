<?php

declare(strict_types=1);

namespace App\Livewire\Calibrations;

use App\Models\Calibration;
use App\Models\CalibrationPoint;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class CalibrationForm extends Component
{
    public Calibration $calibration;

    public float $nominalValue = 0.0;

    public float $measuredValue = 0.0;

    public string $unit = 'mm';

    public float $uncertainty = 0.0;

    public function mount(string $id): void
    {
        $this->calibration = Calibration::with(['instrument', 'standard', 'procedure', 'executor', 'points'])->findOrFail($id);
    }

    public function start(): void
    {
        $this->authorize('update', $this->calibration);
        /** @var User $user */
        $user = Auth::user();
        $this->calibration->start($user);
        $this->calibration->refresh();
        session()->flash('success', 'Calibração iniciada.');
    }

    public function addPoint(): void
    {
        $this->authorize('update', $this->calibration);

        $deviation = $this->measuredValue - $this->nominalValue;

        CalibrationPoint::create([
            'calibration_id' => $this->calibration->id,
            'nominal_value' => $this->nominalValue,
            'measured_value' => $this->measuredValue,
            'unit' => $this->unit,
            'deviation' => $deviation,
            'uncertainty' => $this->uncertainty,
            'pass' => abs($deviation) <= $this->uncertainty,
        ]);

        $this->calibration->load('points');
        $this->reset(['nominalValue', 'measuredValue', 'uncertainty']);
        session()->flash('success', 'Ponto adicionado.');
    }

    public function submitForReview(): void
    {
        $this->authorize('update', $this->calibration);
        $this->calibration->submitForReview();
        $this->calibration->refresh();
        session()->flash('success', 'Calibração enviada para revisão.');
    }

    public function render(): View
    {
        return view('livewire.calibrations.calibration-form', [
            'calibration' => $this->calibration,
        ]);
    }
}
