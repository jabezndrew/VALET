<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\SysUser;
use Carbon\Carbon;

class VehicleManager extends Component
{
    // Form properties
    public $plate_number = '';
    public $vehicle_make = '';
    public $vehicle_model = '';
    public $vehicle_color = '';
    public $vehicle_type = 'car';
    public $rfid_tag = '';
    public $owner_id = '';
    public $expires_at = '';
    
    // Edit mode
    public $editingId = null;
    public $showModal = false;
    
    // Verify vehicle modal
    public $showVerifyModal = false;
    public $verifyRfid = '';
    public $verifyResult = null;
    
    // Filters
    public $search = '';
    public $statusFilter = 'all';
    public $typeFilter = 'all';
    public $ownerRoleFilter = 'all';

    protected $rules = [
        'plate_number' => 'required|string|max:20',
        'vehicle_make' => 'required|string|max:50',
        'vehicle_model' => 'required|string|max:50',
        'vehicle_color' => 'required|string|max:30',
        'vehicle_type' => 'required|in:car,suv,truck,van',
        'rfid_tag' => 'required|string|max:50',
        'owner_id' => 'required|exists:sys_users,id',
        'expires_at' => 'nullable|date|after_or_equal:today',
    ];

    public function render()
    {
        return view('livewire.vehicle-manager', [
            'vehicles' => $this->getVehicles(),
            'users' => SysUser::active()->orderBy('name')->get(),
            'stats' => $this->getVehicleStats()
        ])->layout('layouts.app');
    }

    public function openVerifyModal()
    {
        if (auth()->user()->role === 'user') {
            $this->dispatch('show-alert', type: 'error', message: 'Access denied.');
            return;
        }
        
        $this->reset(['verifyRfid', 'verifyResult']);
        $this->showVerifyModal = true;
    }

    public function closeVerifyModal()
    {
        $this->showVerifyModal = false;
        $this->reset(['verifyRfid', 'verifyResult']);
    }

    public function verifyVehicle()
    {
        if (auth()->user()->role === 'user') {
            $this->dispatch('show-alert', type: 'error', message: 'Access denied.');
            return;
        }

        $this->validate(['verifyRfid' => 'required|string']);

        $vehicle = DB::table('vehicles')
            ->leftJoin('sys_users', 'vehicles.owner_id', '=', 'sys_users.id')
            ->select('vehicles.*', 'sys_users.name as owner_name', 'sys_users.role as owner_role')
            ->where('vehicles.rfid_tag', $this->verifyRfid)
            ->first();

        if (!$vehicle) {
            $this->verifyResult = [
                'status' => 'NOT_FOUND',
                'message' => 'Vehicle not found in system',
                'color' => 'danger'
            ];
            return;
        }

        $isActive = $this->isVehicleActive($vehicle);
        
        $this->verifyResult = [
            'status' => $isActive ? 'Active' : 'Inactive',
            'message' => $isActive 
                ? 'Vehicle is active and authorized for parking.'
                : $this->getInactiveReason($vehicle),
            'vehicle' => $vehicle,
            'color' => $isActive ? 'success' : 'danger'
        ];
    }

    public function openModal($vehicleId = null)
    {
        if ($vehicleId) {
            $vehicle = DB::table('vehicles')->find($vehicleId);
            if ($vehicle) {
                $this->editingId = $vehicleId;
                $this->fill([
                    'plate_number' => $vehicle->plate_number,
                    'vehicle_make' => $vehicle->vehicle_make,
                    'vehicle_model' => $vehicle->vehicle_model,
                    'vehicle_color' => $vehicle->vehicle_color,
                    'vehicle_type' => $vehicle->vehicle_type,
                    'rfid_tag' => $vehicle->rfid_tag,
                    'owner_id' => $vehicle->owner_id,
                    'expires_at' => $vehicle->expires_at ? Carbon::parse($vehicle->expires_at)->format('Y-m-d') : '',
                ]);
            }
        } else {
            $this->resetForm();
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save()
    {
        $this->validate();

        try {
            // Check for duplicates
            if ($this->isDuplicate('plate_number', $this->plate_number)) {
                $this->dispatch('show-alert', type: 'error', message: 'This plate number is already registered.');
                return;
            }

            if ($this->isDuplicate('rfid_tag', $this->rfid_tag)) {
                $this->dispatch('show-alert', type: 'error', message: 'This RFID tag is already in use.');
                return;
            }

            $data = [
                'plate_number' => strtoupper($this->plate_number),
                'vehicle_make' => $this->vehicle_make,
                'vehicle_model' => $this->vehicle_model,
                'vehicle_color' => $this->vehicle_color,
                'vehicle_type' => $this->vehicle_type,
                'rfid_tag' => $this->rfid_tag,
                'owner_id' => $this->owner_id,
                'expires_at' => $this->expires_at ?: null,
                'updated_at' => now(),
            ];

            if ($this->editingId) {
                DB::table('vehicles')->where('id', $this->editingId)->update($data);
                $message = 'Vehicle updated successfully.';
            } else {
                $data['is_active'] = true;
                $data['created_at'] = now();
                DB::table('vehicles')->insert($data);
                $message = 'Vehicle registered successfully.';
            }

            $this->dispatch('show-alert', type: 'success', message: $message);
            $this->closeModal();
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to save vehicle: ' . $e->getMessage());
        }
    }

    public function renewVehicle($vehicleId)
    {
        if (!auth()->user()->canManageCars()) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $newExpiryDate = Carbon::now()->addMonths(6);

        DB::table('vehicles')->where('id', $vehicleId)->update([
            'expires_at' => $newExpiryDate,
            'is_active' => true,
            'updated_at' => now()
        ]);

        $this->dispatch('show-alert', type: 'success', message: 'Vehicle renewed successfully until ' . $newExpiryDate->format('M j, Y'));
    }

    public function toggleStatus($vehicleId)
    {
        if (!auth()->user()->canManageCars()) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $vehicle = DB::table('vehicles')->find($vehicleId);
        if ($vehicle) {
            DB::table('vehicles')->where('id', $vehicleId)->update([
                'is_active' => !$vehicle->is_active,
                'updated_at' => now()
            ]);

            $status = $vehicle->is_active ? 'deactivated' : 'activated';
            $this->dispatch('show-alert', type: 'success', message: "Vehicle {$status} successfully.");
        }
    }

    public function delete($vehicleId)
    {
        if (!auth()->user()->canManageCars()) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        try {
            DB::table('vehicles')->where('id', $vehicleId)->delete();
            $this->dispatch('show-alert', type: 'success', message: 'Vehicle deleted successfully.');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to delete vehicle.');
        }
    }

    // Helper methods
    public function getVehicleStatus($vehicle)
    {
        return $this->isVehicleActive($vehicle) ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeClass($vehicle)
    {
        return $this->isVehicleActive($vehicle) ? 'badge bg-success' : 'badge bg-danger';
    }

    public function getRowClass($vehicle)
    {
        return $this->isVehicleActive($vehicle) ? '' : 'table-danger';
    }

    public function getStatusIcon($vehicle) 
    {
        return $this->isVehicleActive($vehicle) ? 'fas fa-check-circle' : 'fas fa-times-circle';
    }

    public function getExpiryText($expiresAt)
    {
        if (!$expiresAt) return '';
        
        $expiryDate = Carbon::parse($expiresAt);
        return $expiryDate->isPast() 
            ? 'Expired ' . $expiryDate->diffForHumans()
            : 'Expires ' . $expiryDate->diffForHumans();
    }

    private function isVehicleActive($vehicle)
    {
        if (!$vehicle->is_active) return false;
        
        if ($vehicle->expires_at && Carbon::parse($vehicle->expires_at)->isPast()) {
            return false;
        }
        
        return true;
    }

    private function getInactiveReason($vehicle)
    {
        if (!$vehicle->is_active) {
            return 'Vehicle is deactivated. Contact administrator.';
        }
        
        if ($vehicle->expires_at && Carbon::parse($vehicle->expires_at)->isPast()) {
            return 'Vehicle registration expired on ' . Carbon::parse($vehicle->expires_at)->format('M j, Y') . '. Renewal required.';
        }
        
        return 'Vehicle is inactive.';
    }

    private function isDuplicate($field, $value)
    {
        return DB::table('vehicles')
            ->where($field, $value)
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->exists();
    }

    private function resetForm()
    {
        $this->reset([
            'editingId', 'plate_number', 'vehicle_make', 'vehicle_model',
            'vehicle_color', 'rfid_tag', 'owner_id'
        ]);
        $this->vehicle_type = 'car';
        $this->expires_at = now()->format('Y-m-d');
        $this->resetErrorBag();
    }

    private function getVehicles()
    {
        $query = DB::table('vehicles')
            ->leftJoin('sys_users', 'vehicles.owner_id', '=', 'sys_users.id')
            ->select('vehicles.*', 'sys_users.name as owner_name', 'sys_users.role as owner_role');

        // Apply filters
        if ($this->search) {
            $query->where(function ($q) {
                $searchTerms = ['vehicles.plate_number', 'vehicles.vehicle_make', 'vehicles.vehicle_model', 'vehicles.rfid_tag', 'sys_users.name'];
                foreach ($searchTerms as $term) {
                    $q->orWhere($term, 'like', "%{$this->search}%");
                }
            });
        }

        if ($this->statusFilter !== 'all') {
            match($this->statusFilter) {
                'active' => $query->where('vehicles.is_active', true)
                                 ->where(function($q) {
                                     $q->whereNull('vehicles.expires_at')->orWhere('vehicles.expires_at', '>', now());
                                 }),
                'inactive' => $query->where('vehicles.is_active', false),
                'expired' => $query->where('vehicles.expires_at', '<', now())
            };
        }

        if ($this->typeFilter !== 'all') {
            $query->where('vehicles.vehicle_type', $this->typeFilter);
        }

        if ($this->ownerRoleFilter !== 'all') {
            $query->where('sys_users.role', $this->ownerRoleFilter);
        }

        return $query->orderBy('vehicles.created_at', 'desc')->get();
    }

    private function getVehicleStats()
    {
        return [
            'total' => DB::table('vehicles')->count(),
            'active' => DB::table('vehicles')
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
            'inactive' => DB::table('vehicles')->where('is_active', false)->count(),
            'expired' => DB::table('vehicles')->where('expires_at', '<', now())->count(),
            'by_type' => DB::table('vehicles')
                ->select('vehicle_type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('vehicle_type')
                ->pluck('count', 'vehicle_type')
                ->toArray(),
        ];
    }
}