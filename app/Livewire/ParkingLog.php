<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ParkingEntry;
use Carbon\Carbon;

class ParkingLog extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $dateFrom = '';
    public $dateTo = '';
    public $entryTypeFilter = 'all';

    protected $queryString = ['search', 'statusFilter', 'dateFrom', 'dateTo', 'entryTypeFilter'];

    public function mount()
    {
        $allowedRoles = ['admin', 'ssd', 'security'];
        if (!in_array(auth()->user()->role, $allowedRoles)) {
            abort(403, 'Unauthorized access.');
        }

        // Default to today
        $this->dateFrom = now()->startOfDay()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'entryTypeFilter']);
        $this->dateFrom = now()->startOfDay()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function export()
    {
        $entries = $this->getEntriesQuery()->get();

        $csvData = "Entry Type,User,Vehicle Plate,Entry Time,Exit Time,Duration (min),Status\n";

        foreach ($entries as $entry) {
            $userName = $entry->user->name ?? 'Guest';
            $csvData .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $entry->entry_type,
                '"' . str_replace('"', '""', $userName) . '"',
                $entry->vehicle_plate ?? 'N/A',
                $entry->entry_time->format('Y-m-d H:i:s'),
                $entry->exit_time ? $entry->exit_time->format('Y-m-d H:i:s') : 'Still Parked',
                $entry->duration_minutes ?? '-',
                $entry->status
            );
        }

        $filename = 'parking_log_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($csvData) {
            echo $csvData;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function getEntriesQuery()
    {
        $query = ParkingEntry::with(['user', 'rfidTag', 'guestAccess'])
            ->orderBy('entry_time', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('vehicle_plate', 'like', "%{$this->search}%")
                  ->orWhereHas('user', function ($uq) {
                      $uq->where('name', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->entryTypeFilter !== 'all') {
            $query->where('entry_type', $this->entryTypeFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('entry_time', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('entry_time', '<=', $this->dateTo);
        }

        return $query;
    }

    public function getStats()
    {
        $today = now()->startOfDay();

        return [
            'total_today' => ParkingEntry::whereDate('entry_time', $today)->count(),
            'currently_parked' => ParkingEntry::where('status', 'parked')->count(),
            'exited_today' => ParkingEntry::whereDate('exit_time', $today)->count(),
            'avg_duration' => ParkingEntry::whereNotNull('duration_minutes')
                ->whereDate('exit_time', $today)
                ->avg('duration_minutes') ?? 0,
        ];
    }

    public function render()
    {
        return view('livewire.parking-log', [
            'entries' => $this->getEntriesQuery()->paginate(20),
            'stats' => $this->getStats()
        ])->layout('layouts.app');
    }
}
