<!-- resources/views/livewire/auth/register.blade.php -->
<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <div class="valet-logo-large">
                <i class="fas fa-car" style="font-size: 1.5rem; color: #B22020;"></i>
            </div>
            <h2 class="fw-bold mb-2">Create Account</h2>
            <p class="mb-0">VALET Smart Parking System</p>
        </div>
        
        <div class="register-body">
            <form wire:submit.prevent="register">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" wire:model="name" 
                           placeholder="Full Name" required>
                    <label for="name">Full Name</label>
                    @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" wire:model="email" 
                           placeholder="Email" required>
                    <label for="email">Email Address</label>
                    @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-floating mb-3">
                    <select class="form-control" id="role" wire:model="role" required>
                        <option value="">Select Role</option>
                        <option value="user">User</option>
                        <option value="security">Security Personnel</option>
                        <option value="ssd">SSD Personnel</option>
                        <option value="admin">Administrator</option>
                    </select>
                    <label for="role">Role</label>
                    @error('role') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="employee_id" wire:model="employee_id" 
                           placeholder="Employee ID">
                    <label for="employee_id">Employee ID (Optional)</label>
                    @error('employee_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="department" wire:model="department" 
                           placeholder="Department">
                    <label for="department">Department (Optional)</label>
                    @error('department') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" wire:model="password" 
                           placeholder="Password" required>
                    <label for="password">Password</label>
                    @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password_confirmation" 
                           wire:model="password_confirmation" placeholder="Confirm Password" required>
                    <label for="password_confirmation">Confirm Password</label>
                    @error('password_confirmation') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
                
                <button type="submit" class="btn btn-register" wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </span>
                    <span wire:loading>
                        <i class="fas fa-spinner fa-spin me-2"></i>Creating...
                    </span>
                </button>
            </form>
            
            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="text-decoration-none" style="color: #B22020;" wire:navigate>
                    ← Back to Login
                </a>
            </div>
        </div>
    </div>
</div>