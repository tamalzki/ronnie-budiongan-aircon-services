<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2e1065, #6d28d9);
            font-family: 'Inter', sans-serif;
        }

        .main-wrapper {
            width: 95%;
            max-width: 1100px;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 40px 90px rgba(0,0,0,.25);
        }

        /* LEFT SIDE */
        .brand-side {
            background: linear-gradient(160deg, #4c1d95, #6d28d9);
            color: white;
            padding: 70px 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .brand-side img {
            max-width: 230px;
            width: 100%;
            margin-bottom: 30px;
        }

        .brand-title {
            font-weight: 700;
            font-size: 22px;
        }

        .brand-desc {
            font-size: 15px;
            margin-top: 12px;
            opacity: 0.95;
            line-height: 1.6;
        }

        /* RIGHT SIDE */
        .form-side {
            padding: 70px 70px;
            display: flex;
            align-items: center;
        }

        .login-form {
            width: 100%;
            max-width: 380px;
            margin: auto;
        }

        .login-title {
            font-weight: 700;
            font-size: 26px;
            margin-bottom: 8px;
        }

        .login-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 35px;
        }

        .form-label {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .form-control {
            border-radius: 12px;
            padding: 13px 14px;
            font-size: 14px;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: #6d28d9;
            box-shadow: 0 0 0 3px rgba(109,40,217,.18);
        }

        .btn-login {
    background: #6d28d9;
    color: #ffffff !important;
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-weight: 600;
    font-size: 15px;
    letter-spacing: 0.3px;
    transition: all 0.2s ease;
}

        .btn-login:hover,
.btn-login:focus {
    background: #5b21b6;
    color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(109,40,217,.3);
}

.btn-login:active {
    background: #4c1d95;
    color: #ffffff !important;
}

        .form-check-label {
            font-size: 13px;
            color: #4b5563;
        }

        .alert {
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .brand-side {
                padding: 50px 25px;
            }

            .form-side {
                padding: 50px 30px;
            }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="row g-0">

        <!-- LEFT -->
        <div class="col-md-6 brand-side">
            <div>
                <img src="https://i.ibb.co/8Qsd72y/299077745-1391147514710942-648860233429463016-n.jpg"
                     alt="Company Logo">

                <div class="brand-title">
                    {{ config('app.name') }}
                </div>

                <div class="brand-desc">
                    Inventory & Sales Management System<br>
                    Monitor stock levels, track transactions, and manage your business efficiently.
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="col-md-6 form-side">
            <div class="login-form">

                <div class="login-title">Sign In</div>
                <div class="login-subtitle">
                    Access your dashboard securely
                </div>

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               value="{{ old('email') }}"
                               required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password"
                               name="password"
                               class="form-control"
                               required>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" name="remember" id="remember">
                        <label class="form-check-label" for="remember">
                            Keep me signed in
                        </label>
                    </div>

                    <button type="submit" class="btn btn-login w-100">
                        Login to Dashboard
                    </button>

                </form>

            </div>
        </div>

    </div>
</div>

</body>
</html>
