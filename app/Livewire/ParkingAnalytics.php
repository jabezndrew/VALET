<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ParkingEntry;
use Carbon\Carbon;

class ParkingAnalytics extends Component
{
    public $dateFrom = '';
    public $dateTo   = '';

    protected $queryString = ['dateFrom', 'dateTo'];

    public function mount()
    {
        $allowedRoles = ['admin', 'ssd', 'security'];
        if (!in_array(auth()->user()->role, $allowedRoles)) {
            abort(403, 'Unauthorized access.');
        }

        $this->dateFrom = now()->startOfDay()->format('Y-m-d');
        $this->dateTo   = now()->format('Y-m-d');
    }

    public function getChartData()
    {
        // Last 7 days entries
        $dailyLabels = [];
        $dailyData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $date          = now()->subDays($i);
            $dailyLabels[] = $date->format('M d');
            $dailyData[]   = ParkingEntry::whereDate('entry_time', $date)->count();
        }

        // Peak hours (6am–10pm) for selected date range
        $hourQuery = ParkingEntry::query();
        if ($this->dateFrom) {
            $hourQuery->whereDate('entry_time', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $hourQuery->whereDate('entry_time', '<=', $this->dateTo);
        }
        $byHour = $hourQuery->selectRaw('HOUR(entry_time) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        $hourLabels = [];
        $hourData   = [];
        for ($h = 6; $h <= 22; $h++) {
            $hourLabels[] = Carbon::createFromTime($h)->format('g A');
            $hourData[]   = $byHour[$h] ?? 0;
        }

        // RFID vs Guest for selected date range
        $typeQuery = ParkingEntry::query();
        if ($this->dateFrom) {
            $typeQuery->whereDate('entry_time', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $typeQuery->whereDate('entry_time', '<=', $this->dateTo);
        }
        $typeCounts = $typeQuery->selectRaw('entry_type, COUNT(*) as count')
            ->groupBy('entry_type')
            ->pluck('count', 'entry_type')
            ->toArray();

        return [
            'daily'  => ['labels' => $dailyLabels, 'data' => $dailyData],
            'hourly' => ['labels' => $hourLabels, 'data' => $hourData],
            'type'   => [
                'rfid'  => $typeCounts['rfid'] ?? 0,
                'guest' => $typeCounts['guest'] ?? 0,
            ],
        ];
    }

    public function updatedDateFrom()
    {
        $this->dispatch('refreshCharts', chartData: $this->getChartData());
    }

    public function updatedDateTo()
    {
        $this->dispatch('refreshCharts', chartData: $this->getChartData());
    }

    public function render()
    {
        return view('livewire.parking-analytics', [
            'chartData' => $this->getChartData(),
        ])->layout('layouts.app');
    }
}
