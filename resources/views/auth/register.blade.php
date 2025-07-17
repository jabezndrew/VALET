<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register - VALET Smart Parking</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #B22020, #8B1A1A);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        
        .register-header {
            background: #B22020;
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .valet-logo-large {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .register-body {
            padding: 40px;
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
        
        .btn-register {
            background: #B22020;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-weight: bold;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            background: #8B1A1A;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(178, 32, 32, 0.3);
        }
        
        .alert {
            border-radius: 12px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="valet-logo-large">
                    <i class="fas fa-car" style="font-size: 1.5rem; color: #B22020;"></i>
                </div>
                <h2 class="fw-bold mb-2">Create Account</h2>
                <p class="mb-0">VALET Smart Parking System</p>
            </div>
            
            <!-- Body -->
            <div class="register-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </div>
                @endif
                
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="name" name="name" 
                               placeholder="Full Name" value="{{ old('name') }}" required>
                        <label for="name">Full Name</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="Email" value="{{ old('email') }}" required>
                        <label for="email">Email Address</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <select class="form-control" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                            <option value="security" {{ old('role') == 'security' ? 'selected' : '' }}>Security Personnel</option>
                            <option value="ssd" {{ old('role') == 'ssd' ? 'selected' : '' }}>SSD Personnel</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                        </select>
                        <label for="role">Role</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="employee_id" name="employee_id" 
                               placeholder="Employee ID" value="{{ old('employee_id') }}">
                        <label for="employee_id">Employee ID (Optional)</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="department" name="department" 
                               placeholder="Department" value="{{ old('department') }}">
                        <label for="department">Department (Optional)</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password_confirmation" 
                               name="password_confirmation" placeholder="Confirm Password" required>
                        <label for="password_confirmation">Confirm Password</label>
                    </div>
                    
                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <a href="{{ route('login') }}" class="text-decoration-none" style="color: #B22020;">
                        ← Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>