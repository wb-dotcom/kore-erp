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

    {{-- Divider --}}
    <div class="d-flex align-items-center my-4 gap-2">
        <hr style="flex:1; border-color:#e5e7eb; margin:0;">
        <span style="font-size:0.72rem; color:#9ca3af; white-space:nowrap;">or continue with</span>
        <hr style="flex:1; border-color:#e5e7eb; margin:0;">
    </div>

    {{-- Google OAuth --}}
    <a href="{{ route('auth.google') }}"
        style="display:flex;align-items:center;justify-content:center;gap:10px;
               width:100%;padding:10px 16px;border:1.5px solid #e5e7eb;border-radius:8px;
               background:#fff;text-decoration:none;color:#374151;font-size:0.875rem;
               font-weight:500;transition:border-color 0.15s,box-shadow 0.15s;"
        onmouseover="this.style.borderColor='#4c8bf5';this.style.boxShadow='0 0 0 3px rgba(76,139,245,0.1)'"
        onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
        <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Sign in with Google
    </a>

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
