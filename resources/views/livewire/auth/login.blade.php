<!-- resources/views/livewire/auth/login.blade.php -->
<div class="login-container">
    <div class="login-card">
        <div class="row g-0">
            <div class="col-lg-6 col-md-6">
                <div class="login-left">
                    <div class="valet-logo-large">
                        <img src="/images/valet-logo.jpg" alt="VALET" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <i class="fas fa-car" style="display: none; font-size: 2rem; color: #B22020;"></i>
                    </div>
                    
                    <h2 class="fw-bold mb-3">VALET</h2>
                    <p class="mb-4">Your Virtual Assistant LED Enabled Smart Parking Guide</p>
                    
                    <div class="parking-icons">
                        <i class="fas fa-car parking-icon"></i>
                        <i class="fas fa-parking parking-icon"></i>
                        <i class="fas fa-route parking-icon"></i>
                    </div>
                    
                    <p class="small mt-4 opacity-75">
                        University of San Jose-Recoletos<br>
                        Quadricentennial Campus
                    </p>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-6">
                <div class="login-right">
                    <h3 class="fw-bold mb-2">Welcome Back</h3>
                    <p class="text-muted mb-4">Sign in to access your VALET dashboard</p>
                    
                    @if($error)
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ $error }}
                        </div>
                    @endif
                    
                    <form wire:submit.prevent="login">
                        <div class="form-floating mb-3">
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   wire:model="email"
                                   placeholder="name@example.com"
                                   required>
                            <label for="email">
                                <i class="fas fa-envelope me-2"></i>Email Address
                            </label>
                            @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   wire:model="password"
                                   placeholder="Password"
                                   required>
                            <label for="password">
                                <i class="fas fa-lock me-2"></i>Password
                            </label>
                            @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input remember-check" 
                                       type="checkbox" 
                                       id="remember" 
                                       wire:model="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            <a href="#" class="text-decoration-none" style="color: #B22020;">
                                Forgot Password?
                            </a>
                        </div>
                        
                        <button type="submit" class="btn btn-login" wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </span>
                            <span wire:loading>
                                <i class="fas fa-spinner fa-spin me-2"></i>Signing In...
                            </span>
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Need access? Contact your administrator
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>