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

    public function mount()
    {
        $this->ensureVehicleTableExists();
    }

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

        // Check if vehicle is active and not expired
        if (!$vehicle->is_active) {
            $this->verifyResult = [
                'status' => 'INACTIVE',
                'message' => 'Vehicle is deactivated',
                'vehicle' => $vehicle,
                'color' => 'warning'
            ];
            return;
        }

        // Check expiry if column exists
        if ($this->columnExists('vehicles', 'expires_at') && $vehicle->expires_at) {
            $expiryDate = Carbon::parse($vehicle->expires_at);
            
            if ($expiryDate->isPast()) {
                $this->verifyResult = [
                    'status' => 'EXPIRED',
                    'message' => 'Vehicle registration expired on ' . $expiryDate->format('M j, Y'),
                    'vehicle' => $vehicle,
                    'color' => 'danger'
                ];
                return;
            }
            
            if ($expiryDate->diffInDays(Carbon::now()) <= 30) {
                $this->verifyResult = [
                    'status' => 'EXPIRING_SOON',
                    'message' => 'Vehicle expires on ' . $expiryDate->format('M j, Y') . ' (' . $expiryDate->diffForHumans() . ')',
                    'vehicle' => $vehicle,
                    'color' => 'warning'
                ];
                return;
            }
        }

        $this->verifyResult = [
            'status' => 'ACTIVE',
            'message' => 'Vehicle is active and valid',
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

    public function exportVehicles()
    {
        // This would export vehicles to CSV - implement as needed
        $this->dispatch('show-alert', type: 'info', message: 'Export feature coming soon.');
    }

    // Helper methods for UI display - FIXED STATUS DETECTION
    public function getVehicleStatus($vehicle)
{
    if (!$vehicle->is_active) {
        return 'Inactive';
    }

    if (!empty($vehicle->expires_at)) {
        $expiryDate = Carbon::parse($vehicle->expires_at);
        $now = Carbon::now();

        if ($expiryDate->isPast()) {
            return 'Expired';
        } elseif ($now->diffInDays($expiryDate, false) <= 30 && $expiryDate->isFuture()) {
            return 'Expiring Soon';
        }
    }

    return 'Active';
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

        // Only apply expiry-based filters if expires_at column exists
        if ($this->statusFilter !== 'all' && $this->columnExists('vehicles', 'expires_at')) {
            switch ($this->statusFilter) {
                case 'active':
                    $query->where('vehicles.is_active', true)
                          ->where(function($q) {
                              $q->whereNull('vehicles.expires_at')
                                ->orWhere('vehicles.expires_at', '>', now());
                          });
                    break;
                case 'expired':
                    $query->where('vehicles.expires_at', '<', now());
                    break;
                case 'expiring_soon':
                    $query->where('vehicles.expires_at', '>', now())
                          ->where('vehicles.expires_at', '<=', now()->addDays(30));
                    break;
                case 'inactive':
                    $query->where('vehicles.is_active', false);
                    break;
            }
        } elseif ($this->statusFilter !== 'all') {
            // Fallback for tables without expires_at column
            switch ($this->statusFilter) {
                case 'active':
                    $query->where('vehicles.is_active', true);
                    break;
                case 'inactive':
                    $query->where('vehicles.is_active', false);
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

    private function getVehicleStats()
    {
        $stats = [
            'total' => DB::table('vehicles')->count(),
            'active' => DB::table('vehicles')->where('is_active', true)->count(),
            'expired' => 0,
            'expiring_soon' => 0,
            'by_type' => DB::table('vehicles')
                ->select('vehicle_type')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('vehicle_type')
                ->pluck('count', 'vehicle_type')
                ->toArray(),
        ];

        // Only calculate expiry stats if expires_at column exists
        if ($this->columnExists('vehicles', 'expires_at')) {
            $stats['active'] = DB::table('vehicles')
                ->where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->count();
            
            $stats['expired'] = DB::table('vehicles')
                ->where('expires_at', '<', now())
                ->count();
            
            $stats['expiring_soon'] = DB::table('vehicles')
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', now()->addDays(30))
                ->count();
        }

        return $stats;
    }

    private function ensureVehicleTableExists()
    {
        // Create table if it doesn't exist (REMOVED MOTORCYCLE)
        DB::statement("CREATE TABLE IF NOT EXISTS vehicles (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            plate_number VARCHAR(20) UNIQUE NOT NULL,
            vehicle_make VARCHAR(50) NOT NULL,
            vehicle_model VARCHAR(50) NOT NULL,
            vehicle_color VARCHAR(30) NOT NULL,
            vehicle_type ENUM('car', 'suv', 'truck', 'van') DEFAULT 'car',
            rfid_tag VARCHAR(50) UNIQUE NOT NULL,
            owner_id BIGINT UNSIGNED NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_plate_number (plate_number),
            INDEX idx_rfid_tag (rfid_tag),
            INDEX idx_owner_id (owner_id),
            FOREIGN KEY (owner_id) REFERENCES sys_users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB");

        // Add expires_at column if it doesn't exist
        if (!$this->columnExists('vehicles', 'expires_at')) {
            try {
                DB::statement("ALTER TABLE vehicles ADD COLUMN expires_at DATETIME NULL AFTER owner_id");
                DB::statement("ALTER TABLE vehicles ADD INDEX idx_expires_at (expires_at)");
            } catch (\Exception $e) {
                // Column might already exist or other issue, continue
            }
        }
    }
}