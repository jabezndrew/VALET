<div>
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
        
        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><circle cx="20" cy="20" r="15" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/><circle cx="80" cy="80" r="20" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></svg>');
            opacity: 0.1;
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
        
        .valet-logo-large img {
            width: 60px;
            height: 60px;
            object-fit: contain;
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
        
        .form-floating label {
            color: #666;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
        }
        
        .alert-danger {
            background: #fff5f5;
            color: #B22020;
        }
        
        .alert-success {
            background: #f0fff4;
            color: #22c55e;
        }
        
        .remember-check {
            accent-color: #B22020;
        }
        
        .parking-icon {
            font-size: 2rem;
            margin: 10px;
            opacity: 0.8;
        }

        .password-field-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 5px 10px;
            z-index: 10;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #B22020;
        }

        .password-toggle:focus {
            outline: none;
        }

        @media (max-width: 768px) {
            .login-left {
                padding: 40px 30px;
            }

            .login-right {
                padding: 40px 30px;
            }
        }
    </style>

    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Left Side - Branding -->
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
                
                <!-- Right Side - Login Form -->
                <div class="col-lg-6 col-md-6">
                    <div class="login-right">
                        <h3 class="fw-bold mb-2">Welcome Back</h3>
                        <p class="text-muted mb-4">Sign in to access your VALET dashboard</p>
                        
                        <!-- Alert Messages -->
                        @if(session('success'))
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif
                        
                        @if($error)
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                {{ $error }}
                            </div>
                        @endif
                        
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                @foreach($errors->all() as $error)
                                    {{ $error }}<br>
                                @endforeach
                            </div>
                        @endif
                        
                        <!-- LIVEWIRE Login Form -->
                        <form wire:submit.prevent="login">
                            <div class="form-floating mb-3">
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="email"
                                       wire:model="email"
                                       placeholder="name@example.com"
                                       required>
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="form-floating mb-3 password-field-wrapper">
                                <input type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       id="password"
                                       wire:model="password"
                                       placeholder="Password"
                                       required>
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <button type="button"
                                        class="password-toggle"
                                        onclick="togglePasswordVisibility()"
                                        title="Show/Hide Password">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                            
                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
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

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</div>