<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — {{ config('app.name', 'Kore ERP') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1d23 0%, #2d3748 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        .kore-logo {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1d23;
            letter-spacing: -1px;
            text-align: center;
            margin-bottom: 4px;
        }
        .kore-logo span { color: #4c8bf5; }
        .login-subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 0.85rem;
            margin-bottom: 32px;
        }
        .form-label { font-size: 0.8rem; font-weight: 500; color: #374151; }
        .form-control {
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.875rem;
            transition: border-color 0.15s;
        }
        .form-control:focus { border-color: #4c8bf5; box-shadow: 0 0 0 3px rgba(76,139,245,0.1); }
        .btn-login {
            background: #4c8bf5;
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-size: 0.875rem;
            font-weight: 600;
            width: 100%;
            color: #fff;
            transition: background 0.15s;
        }
        .btn-login:hover { background: #3b76e0; color: #fff; }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.8rem;
            color: #991b1b;
            margin-bottom: 20px;
        }
        .forgot-link {
            font-size: 0.78rem;
            color: #4c8bf5;
            text-decoration: none;
        }
        .forgot-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="login-card">

    <div class="kore-logo">KORE <span>ERP</span></div>
    <div class="login-subtitle">Sign in to your workspace</div>

    @if($errors->any())
    <div class="alert-error">
        <i class="bi bi-exclamation-circle me-2"></i>
        {{ $errors->first() }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert-error">
        <i class="bi bi-exclamation-circle me-2"></i>
        {{ session('error') }}
    </div>
    @endif

    @if(session('success'))
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;font-size:0.8rem;color:#166534;margin-bottom:20px;">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email"
                   id="email"
                   name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   placeholder="you@company.com"
                   required
                   autofocus>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label mb-0">Password</label>
                <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
            </div>
            <div class="position-relative">
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="••••••••"
                       required>
                <button type="button"
                        onclick="togglePassword()"
                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9ca3af;cursor:pointer;">
                    <i class="bi bi-eye" id="pwToggleIcon"></i>
                </button>
            </div>
        </div>

        <div class="mb-4 d-flex align-items-center gap-2">
            <input type="checkbox" name="remember" id="remember" class="form-check-input" value="1">
            <label for="remember" style="font-size:0.8rem;color:#6b7280;cursor:pointer;">Remember me</label>
        </div>

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>

    </form>

    <div class="text-center mt-4" style="font-size:0.72rem; color:#9ca3af;">
        {{ config('app.name', 'Kore ERP') }} &mdash; &copy; {{ date('Y') }}
    </div>

</div>

<script>
function togglePassword() {
    const pw   = document.getElementById('password');
    const icon = document.getElementById('pwToggleIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

</body>
</html>
