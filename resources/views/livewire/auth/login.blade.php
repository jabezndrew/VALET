<!-- resources/views/livewire/auth/login.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - VALET Smart Parking</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #B22020, #8B1A1A);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .login-left {
            background: #B22020;
            color: white;
            padding: 60px 40px;
            text-align: center;
            position: relative;
        }
        
        .valet-logo-large {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
            z-index: 2;
        }
        
        .login-right {
            padding: 60px 40px;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #B22020;
            box-shadow: 0 0 0 0.2rem rgba(178, 32, 32, 0.25);
        }
        
        .btn-login {
            background: #B22020;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: bold;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: #8B1A1A;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(178, 32, 32, 0.3);
        }
        
        .parking-icon {
            font-size: 2rem;
            margin: 10px;
            opacity: 0.8;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
    
    @livewireStyles
</head>
<body>
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
                                    <input class="form-check-input" 
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>
</html>