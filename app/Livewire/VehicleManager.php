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
        $vehicles = $this->getVehicles();
        $users = SysUser::where('is_active', true)->orderBy('name')->get();
        $stats = $this->getVehicleStats();
        
        return view('livewire.vehicle-manager', [
            'vehicles' => $vehicles,
            'users' => $users,
            'stats' => $stats
        ])->layout('layouts.app');
    }

    // VERIFY VEHICLE METHODS
    public function openVerifyModal()
    {
        if (auth()->user()->role === 'user') {
            $this->dispatch('show-alert', type: 'error', message: 'Access denied.');
            return;
        }
        
        $this->verifyRfid = '';
        $this->verifyResult = null;
        $this->showVerifyModal = true;
    }

    public function closeVerifyModal()
    {
        $this->showVerifyModal = false;
        $this->verifyRfid = '';
        $this->verifyResult = null;
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
            ->select(
                'vehicles.*',
                'sys_users.name as owner_name',
                'sys_users.role as owner_role'
            )
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

        // Use simplified status logic - only Active or Inactive
        if (!$vehicle->is_active) {
            $this->verifyResult = [
                'status' => 'Inactive',
                'message' => 'Vehicle is deactivated. Contact administrator.',
                'vehicle' => $vehicle,
                'color' => 'danger'
            ];
            return;
        }

        // Check expiry if column exists
        if ($this->columnExists('vehicles', 'expires_at') && $vehicle->expires_at) {
            $expiryDate = Carbon::parse($vehicle->expires_at);
            
            if ($expiryDate->isPast()) {
                $this->verifyResult = [
                    'status' => 'Inactive',
                    'message' => 'Vehicle registration expired on ' . $expiryDate->format('M j, Y') . '. Renewal required.',
                    'vehicle' => $vehicle,
                    'color' => 'danger'
                ];
                return;
            }
        }

        $this->verifyResult = [
            'status' => 'Active',
            'message' => 'Vehicle is active and authorized for parking.',
            'vehicle' => $vehicle,
            'color' => 'success'
        ];
    }

    public function openModal($vehicleId = null)
    {
        if ($vehicleId) {
            $vehicle = DB::table('vehicles')->where('id', $vehicleId)->first();
            if ($vehicle) {
                $this->editingId = $vehicleId;
                $this->plate_number = $vehicle->plate_number;
                $this->vehicle_make = $vehicle->vehicle_make;
                $this->vehicle_model = $vehicle->vehicle_model;
                $this->vehicle_color = $vehicle->vehicle_color;
                $this->vehicle_type = $vehicle->vehicle_type;
                $this->rfid_tag = $vehicle->rfid_tag;
                $this->owner_id = $vehicle->owner_id;
                $this->expires_at = isset($vehicle->expires_at) && $vehicle->expires_at ? Carbon::parse($vehicle->expires_at)->format('Y-m-d') : '';
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
            // Check for duplicate plate number
            $existingPlate = DB::table('vehicles')
                ->where('plate_number', $this->plate_number)
                ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
                ->first();

            if ($existingPlate) {
                $this->dispatch('show-alert', type: 'error', message: 'This plate number is already registered.');
                return;
            }

            // Check for duplicate RFID tag
            $existingRfid = DB::table('vehicles')
                ->where('rfid_tag', $this->rfid_tag)
                ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
                ->first();

            if ($existingRfid) {
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
                'updated_at' => now(),
            ];

            // Only add expires_at if column exists
            if ($this->columnExists('vehicles', 'expires_at')) {
                $data['expires_at'] = $this->expires_at ?: null;
            }

            if ($this->editingId) {
                DB::table('vehicles')->where('id', $this->editingId)->update($data);
                $this->dispatch('show-alert', type: 'success', message: 'Vehicle updated successfully.');
            } else {
                $data['is_active'] = true;
                $data['created_at'] = now();
                DB::table('vehicles')->insert($data);
                $this->dispatch('show-alert', type: 'success', message: 'Vehicle registered successfully.');
            }

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

        if (!$this->columnExists('vehicles', 'expires_at')) {
            $this->dispatch('show-alert', type: 'error', message: 'Expiry feature not available. Please contact administrator.');
            return;
        }

        // Renew for next semester (6 months from now)
        $newExpiryDate = Carbon::now()->addMonths(6);

        DB::table('vehicles')
            ->where('id', $vehicleId)
            ->update([
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

        $vehicle = DB::table('vehicles')->where('id', $vehicleId)->first();
        if ($vehicle) {
            DB::table('vehicles')
                ->where('id', $vehicleId)
                ->update([
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

    // SIMPLIFIED: Only Active/Inactive status
    public function getVehicleStatus($vehicle)
    {
        // If vehicle is manually deactivated
        if (!$vehicle->is_active) {
            return 'Inactive';
        }

        // If vehicle has expiry date and it's past
        if (!empty($vehicle->expires_at)) {
            $expiryDate = Carbon::parse($vehicle->expires_at);
            if ($expiryDate->isPast()) {
                return 'Inactive';
            }
        }

        // Otherwise it's active
        return 'Active';
    }

    // UPDATED: Simplified methods for UI display
    public function getRowClass($vehicle)
    {
        $status = $this->getVehicleStatus($vehicle);
        
        return match($status) {
            'Inactive' => 'table-danger',
            'Active' => '',
            default => ''
        };
    }

    public function getStatusBadgeClass($vehicle)
    {
        $status = $this->getVehicleStatus($vehicle);
        
        return match($status) {
            'Inactive' => 'badge bg-danger',
            'Active' => 'badge bg-success',
            default => 'badge bg-secondary'
        };
    }

    public function getStatusText($vehicle)
    {
        return $this->getVehicleStatus($vehicle);
    }

    public function getStatusIcon($vehicle) 
    {
        $status = $this->getVehicleStatus($vehicle);
        
        return match($status) {
            'Inactive' => 'fas fa-times-circle',
            'Active' => 'fas fa-check-circle',
            default => 'fas fa-question-circle'
        };
    }

    public function getDaysUntilExpiry($expiresAt)
    {
        if (!$expiresAt) return '';
        
        $expiryDate = Carbon::parse($expiresAt);
        
        if ($expiryDate->isPast()) {
            return 'Expired ' . $expiryDate->diffForHumans();
        }
        
        return 'Expires ' . $expiryDate->diffForHumans();
    }

    public function isExpired($expiresAt)
    {
        if (!$expiresAt) return false;
        return Carbon::parse($expiresAt)->isPast();
    }

    public function isExpiringSoon($expiresAt)
    {
        if (!$expiresAt) return false;
        $expiryDate = Carbon::parse($expiresAt);
        return $expiryDate->isFuture() && $expiryDate->diffInDays(Carbon::now()) <= 30;
    }

    private function columnExists($table, $column)
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
            return !empty($columns);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->plate_number = '';
        $this->vehicle_make = '';
        $this->vehicle_model = '';
        $this->vehicle_color = '';
        $this->vehicle_type = 'car';
        $this->rfid_tag = '';
        $this->owner_id = '';
        $this->expires_at = '';
        $this->resetErrorBag();
    }

    private function getVehicles()
    {
        $query = DB::table('vehicles')
            ->leftJoin('sys_users', 'vehicles.owner_id', '=', 'sys_users.id')
            ->select(
                'vehicles.*',
                'sys_users.name as owner_name',
                'sys_users.role as owner_role'
            );

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('vehicles.plate_number', 'like', "%{$this->search}%")
                  ->orWhere('vehicles.vehicle_make', 'like', "%{$this->search}%")
                  ->orWhere('vehicles.vehicle_model', 'like', "%{$this->search}%")
                  ->orWhere('vehicles.rfid_tag', 'like', "%{$this->search}%")
                  ->orWhere('sys_users.name', 'like', "%{$this->search}%");
            });
        }

        // FIXED: Updated status filtering to handle all expected filter values
        if ($this->statusFilter !== 'all') {
            switch ($this->statusFilter) {
                case 'active':
                    $query->where('vehicles.is_active', true)
                          ->where(function($q) {
                              $q->whereNull('vehicles.expires_at')
                                ->orWhere('vehicles.expires_at', '>', now());
                          });
                    break;
                case 'inactive':
                    $query->where('vehicles.is_active', false);
                    break;
                case 'expired':
                    $query->where('vehicles.expires_at', '<', now());
                    break;
                case 'expiring_soon':
                    // This filter will return no results since we don't use this status anymore
                    $query->where('vehicles.id', '=', -1); // Force empty result
                    break;
            }
        }

        if ($this->typeFilter !== 'all') {
            $query->where('vehicles.vehicle_type', $this->typeFilter);
        }

        if ($this->ownerRoleFilter !== 'all') {
            $query->where('sys_users.role', $this->ownerRoleFilter);
        }

        return $query->orderBy('vehicles.created_at', 'desc')->get();
    }

    // FIXED: Updated stats to include all expected keys
    private function getVehicleStats()
    {
        $stats = [
            'total' => DB::table('vehicles')->count(),
            'active' => 0,
            'inactive' => 0,
            'expired' => 0,        // Keep this for Blade compatibility
            'expiring_soon' => 0,  // Keep this for Blade compatibility
            'by_type' => DB::table('vehicles')
                ->select('vehicle_type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('vehicle_type')
                ->pluck('count', 'vehicle_type')
                ->toArray(),
        ];

        // Calculate stats with simplified logic
        if ($this->columnExists('vehicles', 'expires_at')) {
            // Active: is_active = true AND (no expiry OR expiry in future)
            $stats['active'] = DB::table('vehicles')
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->count();
            
            // Expired: has expiry date and it's in the past
            $stats['expired'] = DB::table('vehicles')
                ->where('expires_at', '<', now())
                ->count();
                
            // Inactive: manually deactivated (regardless of expiry)
            $stats['inactive'] = DB::table('vehicles')
                ->where('is_active', false)
                ->count();
            
            // Expiring Soon: set to 0 since we're not using this anymore
            $stats['expiring_soon'] = 0;
            
        } else {
            // Fallback for tables without expires_at column
            $stats['active'] = DB::table('vehicles')->where('is_active', true)->count();
            $stats['inactive'] = DB::table('vehicles')->where('is_active', false)->count();
            $stats['expired'] = 0;
            $stats['expiring_soon'] = 0;
        }

        return $stats;
    }
}