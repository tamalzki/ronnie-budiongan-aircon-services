<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Ronnie Budiongan Aircon Services</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --sidebar-width: 220px;
            --primary-color: #4F46E5;
            --primary-dark: #4338CA;
            --secondary-color: #06B6D4;
            --accent-color: #F59E0B;
            --success-color: #10B981;
            --danger-color: #EF4444;
            --warning-color: #F59E0B;
            --text-dark: #1F2937;
            --text-light: #6B7280;
            --bg-light: #F9FAFB;
            --border-color: #E5E7EB;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, #3730a3 0%, #4338CA 60%, #4F46E5 100%);
            color: white;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── Header ── */
        .sidebar-header {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.12);
            flex-shrink: 0;
        }

        .logo {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: white;
            min-width: 0;
        }

        .sidebar-brand-logo {
            display: block;
            flex-shrink: 0;
            width: auto;
            height: auto;
            max-height: 36px;
            max-width: 64px;
            object-fit: contain;
            object-position: center;
        }

        .logo-text {
            flex: 1;
            min-width: 0;
        }

        .logo-title {
            font-size: 0.78rem;
            font-weight: 700;
            line-height: 1.2;
            margin: 0;
            letter-spacing: -0.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            /* Bootstrap sets h1 color via --bs-heading-color (dark); force readable on sidebar */
            color: #fff !important;
        }

        /* ── Navigation ── */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 0.6rem 0 0.5rem;
        }

        .sidebar-nav::-webkit-scrollbar { width: 3px; }
        .sidebar-nav::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 2px; }

        .nav-section { margin-bottom: 0.2rem; }

        .nav-section-title {
            padding: 0.5rem 1rem 0.2rem;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255, 255, 255, 0.45);
        }

        .nav-menu { list-style: none; padding: 0; margin: 0; }

        .nav-item { margin-bottom: 1px; }

        /* ── Fix Bootstrap tabs conflicting with sidebar nav-link styles ── */
        .nav-tabs .nav-link {
            color: var(--text-light) !important;
            background: transparent !important;
            border: 1px solid transparent;
            border-radius: 0;
            font-weight: 500;
            padding: 0.6rem 1.25rem;
        }
        .nav-tabs .nav-link:hover {
            color: var(--primary-color) !important;
            background: transparent !important;
            border-color: var(--border-color) var(--border-color) transparent;
            transform: none;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary-color) !important;
            background: #fff !important;
            border-color: var(--border-color) var(--border-color) #fff;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active::before { display: none !important; }
        .nav-tabs .nav-link .badge { vertical-align: middle; }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.42rem 1rem;
            color: rgba(255, 255, 255, 0.82);
            text-decoration: none;
            transition: background 0.15s ease, color 0.15s ease;
            position: relative;
            font-size: 15px;
            font-weight: 500;
            border-radius: 0;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 600;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--secondary-color);
            border-radius: 0 3px 3px 0;
        }

        .nav-link i {
            width: 18px;
            font-size: 0.9rem;
            margin-right: 0.55rem;
            opacity: 0.9;
            flex-shrink: 0;
        }

        /* ── Footer ── */
        .sidebar-footer {
            flex-shrink: 0;
            padding: 0.65rem 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(0, 0, 0, 0.15);
        }

        .footer-inner {
            display: flex;
            align-items: center;
            gap: 0.55rem;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.65rem;
            flex-shrink: 0;
            letter-spacing: 0.5px;
        }

        .user-details {
            flex: 1;
            min-width: 0;
        }

        .user-details .user-name {
            font-size: 0.78rem;
            font-weight: 600;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }

        .user-details .user-role {
            font-size: 0.62rem;
            margin: 0;
            opacity: 0.6;
            line-height: 1.2;
        }

        .logout-btn {
            width: 28px;
            height: 28px;
            background: rgba(239, 68, 68, 0.18);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: rgba(255, 255, 255, 0.85);
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            cursor: pointer;
            padding: 0;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.35);
            border-color: rgba(239, 68, 68, 0.6);
            color: white;
        }

        /* ── Main Content ── */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--bg-light);
        }

        .content-wrapper {
            padding: 2rem;
        }

        /* ── Alerts ── */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
        }

        /* ── Cards ── */
        .card {
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            border-radius: 12px 12px 0 0 !important;
            border-bottom: 1px solid var(--border-color);
        }

        /* ── Buttons ── */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        /* ── Tables ── */
        .table thead th {
            background: var(--bg-light);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-light);
        }

        /* ── Badges ── */
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8rem;
        }

        /* ── Unified page chrome (lists & detail) ── */
        .app-page-title {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.3;
            color: var(--text-dark);
        }
        .app-page-subtitle {
            font-size: 0.82rem;
            color: var(--text-light);
        }
        .app-card-panel {
            border: none !important;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .app-table-compact {
            font-size: 0.82rem;
        }
        .app-table-compact thead th,
        .app-table-compact tbody td {
            vertical-align: middle;
        }
        .app-filter-toolbar .card-body {
            padding: 0.5rem 0.75rem;
        }
        .app-flash.alert {
            margin-bottom: 0.75rem;
        }
        .app-tab-panel {
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 12px 12px;
            background: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- Logo -->
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="logo" title="Ronnie Aircon">
                <img src="{{ asset('images/brand-logo.png') }}" alt="Ronnie Aircon" class="sidebar-brand-logo" width="64" height="36">
                <div class="logo-text">
                    <h1 class="logo-title">Ronnie Aircon</h1>
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <!-- Main -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Sales & Customers -->
            <div class="nav-section">
                <div class="nav-section-title">Sales & Customers</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                            <i class="bi bi-cart-check"></i>
                            <span>Sales</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('installments.index') }}" class="nav-link {{ request()->routeIs('installments.*') ? 'active' : '' }}">
                            <i class="bi bi-calendar-check"></i>
                            <span>Installments</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Inventory -->
            <div class="nav-section">
                <div class="nav-section-title">Inventory</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                            <i class="bi bi-box-seam"></i>
                            <span>Manage Stock</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <i class="bi bi-grid"></i>
                            <span>Products</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Suppliers -->
            <div class="nav-section">
                <div class="nav-section-title">Suppliers</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="{{ route('purchase-orders.index') }}" class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                            <i class="bi bi-cart-plus"></i>
                            <span>Purchase Orders</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('supplier-payments.index') }}" class="nav-link {{ request()->routeIs('supplier-payments.*') ? 'active' : '' }}">
                            <i class="bi bi-cash-coin"></i>
                            <span>Payments to Supplier</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i>
                            <span>Suppliers</span>
                        </a>
                    </li>
                </ul>
            </div>

            @if(Route::has('operation-expenses.index'))
            <!-- Operations (hidden until operational expense routes are registered, e.g. after deploy + route:clear) -->
            <div class="nav-section">
                <div class="nav-section-title">Operations</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="{{ route('operation-expenses.index') }}" class="nav-link {{ request()->routeIs('operation-expenses.*') ? 'active' : '' }}">
                            <i class="bi bi-receipt-cutoff"></i>
                            <span>Operational expenses</span>
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            <!-- Configuration -->
            <div class="nav-section">
                <div class="nav-section-title">Configuration</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="{{ route('brands.index') }}" class="nav-link {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                            <i class="bi bi-tag"></i>
                            <span>Brands</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}">
                            <i class="bi bi-tools"></i>
                            <span>Services</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Reports -->
            <div class="nav-section">
                <div class="nav-section-title">Analytics</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <i class="bi bi-graph-up"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- User / Logout -->
        <div class="sidebar-footer">
            <div class="footer-inner">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="user-details">
                    <p class="user-name">{{ auth()->user()->name }}</p>
                    <p class="user-role">Administrator</p>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="mb-0">
                    @csrf
                    <button type="submit" class="logout-btn" title="Sign Out">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>