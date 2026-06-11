<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand-logo.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --login-accent: #6366f1;
            --login-accent-hover: #4f46e5;
            --login-accent-ring: rgba(99, 102, 241, 0.25);
            --login-text-muted: #64748b;
            --login-border: #e2e8f0;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background:
                radial-gradient(ellipse 120% 80% at 0% 0%, rgba(99, 102, 241, 0.08), transparent 50%),
                radial-gradient(ellipse 100% 70% at 100% 100%, rgba(6, 182, 212, 0.07), transparent 45%),
                linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .main-wrapper {
            width: 100%;
            max-width: 960px;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow:
                0 1px 3px rgba(15, 23, 42, 0.06),
                0 24px 48px -12px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .login-powered-by {
            background: #f8fafc;
            border-top: 1px solid var(--login-border);
            font-size: 0.8125rem;
            color: #64748b;
            letter-spacing: 0.02em;
        }

        .login-powered-by strong {
            color: #475569;
            font-weight: 600;
        }

        .brand-side {
            background: linear-gradient(155deg, #5b21b6 0%, #6d28d9 42%, #0e7490 100%);
            color: #fff;
            padding: clamp(2.5rem, 6vw, 4rem) clamp(1.75rem, 4vw, 3rem);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 320px;
        }

        .brand-inner {
            text-align: center;
            max-width: 320px;
        }

        .brand-logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.75rem;
            background: #ffffff;
            border-radius: 20px;
            box-shadow:
                0 4px 6px -1px rgba(0, 0, 0, 0.08),
                0 12px 24px -8px rgba(0, 0, 0, 0.15);
        }

        .brand-logo-wrap img {
            display: block;
            width: 100%;
            max-width: 220px;
            height: auto;
        }

        .brand-title {
            font-weight: 700;
            font-size: clamp(1.25rem, 2.5vw, 1.375rem);
            letter-spacing: -0.02em;
            line-height: 1.3;
            margin: 0;
        }

        .brand-desc {
            font-size: 0.9375rem;
            margin-top: 0.75rem;
            margin-bottom: 0;
            opacity: 0.92;
            line-height: 1.55;
            font-weight: 400;
        }

        .form-side {
            padding: clamp(2.5rem, 6vw, 3.5rem) clamp(1.75rem, 5vw, 3rem);
            display: flex;
            align-items: center;
            background: #fff;
        }

        .login-form {
            width: 100%;
            max-width: 360px;
            margin: 0 auto;
        }

        .login-kicker {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--login-accent);
            margin-bottom: 0.5rem;
        }

        .login-title {
            font-weight: 700;
            font-size: 1.625rem;
            letter-spacing: -0.03em;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }

        .login-subtitle {
            color: var(--login-text-muted);
            font-size: 0.9375rem;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.8125rem;
            color: #334155;
            margin-bottom: 0.4rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem 0.9rem;
            font-size: 0.9375rem;
            border: 1px solid var(--login-border);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-control:focus {
            border-color: var(--login-accent);
            box-shadow: 0 0 0 3px var(--login-accent-ring);
        }

        .btn-login {
            background: var(--login-accent);
            color: #ffffff !important;
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: background 0.15s ease, box-shadow 0.15s ease;
        }

        .btn-login:hover,
        .btn-login:focus {
            background: var(--login-accent-hover);
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
        }

        .btn-login:active {
            background: #4338ca;
            color: #ffffff !important;
        }

        .form-check-label {
            font-size: 0.8125rem;
            color: #475569;
        }

        .alert {
            font-size: 0.8125rem;
            border-radius: 12px;
        }

        @media (max-width: 767.98px) {
            .brand-side {
                min-height: auto;
                padding-top: 2rem;
                padding-bottom: 2rem;
            }

            .brand-logo-wrap {
                margin-bottom: 1.25rem;
            }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="row g-0">

        <div class="col-md-6 brand-side">
            <div class="brand-inner">
                <div class="brand-logo-wrap">
                    <img src="{{ asset('images/brand-logo.png') }}" width="220" height="80" alt="{{ config('app.name') }}">
                </div>
                <h1 class="brand-title">{{ config('app.name') }}</h1>
                <p class="brand-desc">
                    Inventory and sales in one place. Track stock, orders, and collections with confidence.
                </p>
            </div>
        </div>

        <div class="col-md-6 form-side">
            <div class="login-form">
                <div class="login-kicker">Secure access</div>
                <div class="login-title">Sign in</div>
                <div class="login-subtitle">Use your work email and password to continue.</div>

                @if($errors->any())
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-control"
                               value="{{ old('email') }}"
                               required
                               autofocus
                               autocomplete="username">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               required
                               autocomplete="current-password">
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Keep me signed in on this device</label>
                    </div>

                    <button type="submit" class="btn btn-login w-100">
                        Continue to dashboard
                    </button>
                </form>
            </div>
        </div>

    </div>

    <p class="login-powered-by text-center py-3 px-4 mb-0">
        Powered by <strong>Trinity Software</strong>
    </p>
</div>

</body>
</html>
