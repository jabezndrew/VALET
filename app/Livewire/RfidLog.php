<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RfidScanLog;

class RfidLog extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    public $dateFrom='';
    public $dateTo='';

    protected $queryString = ['search', 'statusFilter', 'typeFilter', 'dateFrom', 'dateTo'];

    public function mount()
    {
        $allowedRoles=['admin', 'ssd', 'security'];
        if(!in_array(auth()->user()->role, $allowedRoles)){
            abort(403, 'Unauthorized');
        }
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updating(){
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'typeFilter']);
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function getQuery()
    {
        $query = RfidScanLog::orderBy('created_at', 'desc');

        if($this->search){
            $query->where(function($q){
                $q->where('uid', 'like', "%{$this->search}%")
                ->orWhere('user_name', 'like', "%{$this->search}%")
                ->orWhere('vehicle_plate', 'like', "%{$this->search}%");
            });
        }
        
        if($this->statusFilter !== 'all'){
            $query->where('status', $this->statusFilter);
        }

        if($this->typeFilter !== 'all'){
            $query->where('scan_type', $this->typeFilter);
        }
        if($this->dateFrom){
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if($this->dateTo){
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        return $query;
    }

    public function render()
    {
        return view('livewire.rfid-log', [
            'logs' => $this->getQuery()->paginate(25),
        ])->layout('layouts.app');
    }
    
}
