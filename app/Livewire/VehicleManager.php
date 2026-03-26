<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SysUser;
use App\Models\Vehicle;
use App\Models\RfidTag;
use Carbon\Carbon;

class VehicleManager extends Component
{
    // Form properties
    public $plate_number = '';
    public $vehicle_make = '';
    public $vehicle_model = '';
    public $vehicle_color = '';
    public $vehicle_type = 'car';
    public $owner_id = '';
    public $expires_at = '';
    
    // Edit mode
    public $editingId = null;
    public $showModal = false;
    
    // Verify vehicle modal
    public $showVerifyModal = false;
    public $verifyRfid = '';
    public $verifyPlate = '';
    public $verifyMode = 'rfid'; // 'rfid' or 'guest'
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

        $this->reset(['verifyRfid', 'verifyPlate', 'verifyResult']);
        $this->verifyMode = 'rfid';
        $this->showVerifyModal = true;
    }

    public function closeVerifyModal()
    {
        $this->showVerifyModal = false;
        $this->reset(['verifyRfid', 'verifyPlate', 'verifyResult']);
        $this->verifyMode = 'rfid';
    }

    public function setVerifyMode($mode)
    {
        $this->verifyMode = $mode;
        $this->reset(['verifyRfid', 'verifyPlate', 'verifyResult']);
    }

    public function verifyVehicle()
    {
        if (auth()->user()->role === 'user') {
            $this->dispatch('show-alert', type: 'error', message: 'Access denied.');
            return;
        }

        if ($this->verifyMode === 'rfid') {
            $this->verifyByRfid();
        } else {
            $this->verifyByPlate();
        }
    }

    private function verifyByRfid()
    {
        $this->validate(['verifyRfid' => 'required|string']);

        $rfidTag = RfidTag::with(['user', 'vehicle.owner'])->where('uid', strtoupper(trim($this->verifyRfid)))->first();

        if (!$rfidTag || !$rfidTag->vehicle) {
            $this->verifyResult = [
                'status' => 'NOT_FOUND',
                'message' => 'Vehicle not found in system',
                'color' => 'danger',
                'type' => 'rfid'
            ];
            return;
        }

        $vehicle = $rfidTag->vehicle;
        $isActive = $vehicle->isValid() && $rfidTag->status === 'active';

        $this->verifyResult = [
            'status' => $isActive ? 'Active' : 'Inactive',
            'message' => $isActive
                ? 'Vehicle is active and authorized for parking.'
                : $this->getInactiveReason($vehicle),
            'vehicle' => $this->transformVehicleData($vehicle),
            'color' => $isActive ? 'success' : 'danger',
            'type' => 'rfid'
        ];
    }

    private function verifyByPlate()
    {
        $this->validate(['verifyPlate' => 'required|string']);

        $plateNumber = strtoupper(trim($this->verifyPlate));
        $vehicle = Vehicle::with('owner')->where('plate_number', $plateNumber)->first();

        if ($vehicle) {
            $isActive = $vehicle->isValid();

            $this->verifyResult = [
                'status' => 'REGISTERED',
                'message' => $isActive
                    ? 'This vehicle is registered in the system. Owner should use RFID.'
                    : 'This vehicle is registered but ' . $this->getInactiveReason($vehicle),
                'vehicle' => $this->transformVehicleData($vehicle),
                'color' => 'warning',
                'type' => 'guest'
            ];
        } else {
            $this->verifyResult = [
                'status' => 'GUEST_OK',
                'message' => 'Vehicle not registered. Guest access can be granted.',
                'plate' => $plateNumber,
                'color' => 'success',
                'type' => 'guest'
            ];
        }
    }

    public function openModal($vehicleId = null)
    {
        if ($vehicleId) {
            $vehicle = Vehicle::find($vehicleId);
            if ($vehicle) {
                $this->editingId = $vehicleId;
                $this->fill([
                    'plate_number' => $vehicle->plate_number,
                    'vehicle_make' => $vehicle->vehicle_make,
                    'vehicle_model' => $vehicle->vehicle_model,
                    'vehicle_color' => $vehicle->vehicle_color,
                    'vehicle_type' => $vehicle->vehicle_type,
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

            $data = [
                'plate_number' => strtoupper($this->plate_number),
                'vehicle_make' => $this->vehicle_make,
                'vehicle_model' => $this->vehicle_model,
                'vehicle_color' => $this->vehicle_color,
                'vehicle_type' => $this->vehicle_type,
                'owner_id' => $this->owner_id,
                'expires_at' => $this->expires_at ?: null,
            ];

            if ($this->editingId) {
                $vehicle = Vehicle::find($this->editingId);
                $vehicle->update($data);
                // Sync linked RFID tag's expiry date when vehicle expiry changes
                if ($vehicle->rfidTag && $this->expires_at) {
                    $vehicle->rfidTag->update(['expiry_date' => $this->expires_at ?: null]);
                }
                $message = 'Vehicle updated successfully.';
            } else {
                $data['is_active'] = true;
                Vehicle::create($data);
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

        $vehicle = Vehicle::find($vehicleId);
        $vehicle->update([
            'expires_at' => $newExpiryDate,
            'is_active' => true,
        ]);

        $this->dispatch('show-alert', type: 'success', message: 'Vehicle renewed successfully until ' . $newExpiryDate->format('M j, Y'));
    }

    public function toggleStatus($vehicleId)
    {
        if (!auth()->user()->canManageCars()) {
            $this->dispatch('show-alert', type: 'error', message: 'Unauthorized action.');
            return;
        }

        $vehicle = Vehicle::find($vehicleId);
        if ($vehicle) {
            $vehicle->update([
                'is_active' => !$vehicle->is_active,
            ]);

            $status = $vehicle->is_active ? 'activated' : 'deactivated';
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
            $vehicle = Vehicle::find($vehicleId);
            if ($vehicle) {
                $vehicle->delete();
            }
            $this->dispatch('show-alert', type: 'success', message: 'Vehicle deleted successfully.');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', type: 'error', message: 'Failed to delete vehicle.');
        }
    }

    // Helper methods
    public function getVehicleStatus($vehicle)
    {
        return $this->isVehicleValid($vehicle) ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeClass($vehicle)
    {
        return $this->isVehicleValid($vehicle) ? 'badge bg-success' : 'badge bg-danger';
    }

    public function getRowClass($vehicle)
    {
        return $this->isVehicleValid($vehicle) ? '' : 'table-danger';
    }

    public function getStatusIcon($vehicle)
    {
        return $this->isVehicleValid($vehicle) ? 'fas fa-check-circle' : 'fas fa-times-circle';
    }

    private function isVehicleValid($vehicle): bool
    {
        if ($vehicle instanceof Vehicle) {
            return $vehicle->isValid();
        }
        return $this->isVehicleActive($vehicle);
    }

    private function transformVehicleData(Vehicle $vehicle): object
    {
        $data = $vehicle->toArray();
        $data['owner_name'] = $vehicle->owner->name;
        $data['owner_role'] = $vehicle->owner->role;
        return (object) $data;
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
        return Vehicle::where($field, $value)
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->exists();
    }

    private function resetForm()
    {
        $this->reset([
            'editingId', 'plate_number', 'vehicle_make', 'vehicle_model',
            'vehicle_color', 'owner_id'
        ]);
        $this->vehicle_type = 'car';
        $this->expires_at = now()->addMonths(6)->format('Y-m-d');
        $this->resetErrorBag();
    }

    private function getVehicles()
    {
        $query = Vehicle::with(['owner', 'rfidTag']);

        // Apply filters
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('plate_number', 'like', "%{$this->search}%")
                  ->orWhere('vehicle_make', 'like', "%{$this->search}%")
                  ->orWhere('vehicle_model', 'like', "%{$this->search}%")
                  ->orWhere('rfid_tag', 'like', "%{$this->search}%")
                  ->orWhereHas('owner', function ($ownerQuery) {
                      $ownerQuery->where('name', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->statusFilter !== 'all') {
            match($this->statusFilter) {
                'active' => $query->where('is_active', true)
                                 ->where(function($q) {
                                     $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                                 }),
                'inactive' => $query->where('is_active', false),
                'expired' => $query->where('expires_at', '<', now())
            };
        }

        if ($this->typeFilter !== 'all') {
            $query->where('vehicle_type', $this->typeFilter);
        }

        if ($this->ownerRoleFilter !== 'all') {
            $query->whereHas('owner', function ($ownerQuery) {
                $ownerQuery->where('role', $this->ownerRoleFilter);
            });
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($vehicle) {
            $vehicleArray = $vehicle->toArray();
            $vehicleArray['owner_name'] = $vehicle->owner->name ?? null;
            $vehicleArray['owner_role'] = $vehicle->owner->role ?? null;
            $vehicleArray['rfid_uid'] = $vehicle->rfidTag->uid ?? null;
            $vehicleArray['expires_at'] = $vehicle->expires_at ? $vehicle->expires_at->format('Y-m-d') : null;
            return (object) $vehicleArray;
        });
    }

    private function getVehicleStats()
    {
        return [
            'total' => Vehicle::count(),
            'active' => Vehicle::valid()->count(),
            'inactive' => Vehicle::where('is_active', false)->count(),
            'expired' => Vehicle::expired()->count(),
            'by_type' => Vehicle::query()
                ->select('vehicle_type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('vehicle_type')
                ->pluck('count', 'vehicle_type')
                ->toArray(),
        ];
    }
}