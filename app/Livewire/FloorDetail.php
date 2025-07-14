<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FloorDetail extends Component
{
    public $floorLevel;
    public $spaces = [];
    public $floorStats = [];
    public $lastUpdate;

    public function mount($floorLevel)
    {
        $this->floorLevel = $floorLevel;
        $this->loadFloorData();
    }

    public function loadFloorData()
    {
        try {
            // Get spaces for this specific floor
            $this->spaces = DB::table('parking_spaces')
                ->where('floor_level', $this->floorLevel)
                ->orderBy('sensor_id')
                ->get()
                ->map(function ($space) {
                    $space->created_at = Carbon::parse($space->created_at);
                    $space->updated_at = Carbon::parse($space->updated_at);
                    return $space;
                })
                ->toArray();

            // Calculate floor stats
            $total = count($this->spaces);
            $occupied = collect($this->spaces)->where('is_occupied', true)->count();
            $available = $total - $occupied;

            $this->floorStats = [
                'total' => $total,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100, 1) : 0
            ];

            $this->lastUpdate = now()->format('H:i:s');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load floor data: ' . $e->getMessage());
            $this->spaces = [];
            $this->floorStats = ['total' => 0, 'occupied' => 0, 'available' => 0, 'occupancy_rate' => 0];
        }
    }

    public function getSpaceIcon($space)
    {
        if ($space['is_occupied']) {
            return 'fas fa-car';
        }
        return 'fas fa-check-circle';
    }

    public function getStatusText($space)
    {
        if ($space['is_occupied']) {
            return '🚗 Vehicle Present';
        }
        return '✅ Space Available';
    }

    public function render()
    {
        return view('livewire.floor-detail');
    }
}