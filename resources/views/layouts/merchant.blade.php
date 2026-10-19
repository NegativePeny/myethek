<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0f766e">
    <title>@yield('title', 'Portal Mitra - Mak Ethek')</title>

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --merchant-primary: #0f766e;
            --merchant-dark: #115e59;
            --merchant-light: #f0fdfa;
            --text-dark: #0f172a;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
            color: var(--text-dark);
            margin: 0;
            padding: 0;
        }

        .merchant-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            background: #ffffff;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.05);
            position: relative;
            display: flex;
            flex-direction: column;
            padding-bottom: 74px;
        }

        .merchant-header {
            background-color: #0f766e;
            color: white;
            padding: 12px 16px;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        /* Bottom Nav for Merchant */
        .merchant-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-around;
            padding: 8px 0 6px;
            z-index: 1030;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
        }

        .m-nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #64748b;
            text-decoration: none;
            font-size: 0.72rem;
            font-weight: 500;
        }

        .m-nav-link i {
            font-size: 1.25rem;
            margin-bottom: 2px;
        }

        .m-nav-link.active {
            color: #0f766e;
            font-weight: 600;
        }
    </style>
    @yield('styles')
</head>
<body>

<div class="merchant-container">
    <header class="merchant-header">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-check fs-4"></i>
                <div>
                    <h6 class="fw-bold mb-0" style="font-size: 0.95rem;">Portal Mitra Koperasi</h6>
                    <small class="opacity-75" style="font-size: 0.7rem;">Ekosistem Mak Ethek Jatim</small>
                </div>
            </div>
            <a href="{{ route('storefront.index') }}" class="btn btn-sm btn-outline-light py-1 px-2" style="font-size: 0.7rem;">
                <i class="bi bi-shop me-1"></i> Ke Toko Warga
            </a>
        </div>

        @yield('header_extra')
    </header>

    <main class="flex-grow-1">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mx-3 my-2 py-2 px-3 small" role="alert">
                <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mx-3 my-2 py-2 px-3 small" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Merchant Bottom Navigation -->
    <nav class="merchant-bottom-nav">
        <a href="{{ route('merchant.index') }}" class="m-nav-link {{ request()->routeIs('merchant.index') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>Beranda</span>
        </a>
        <a href="{{ route('merchant.orders') }}" class="m-nav-link {{ request()->routeIs('merchant.orders') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i>
            <span>Pesanan</span>
        </a>
        <a href="{{ route('merchant.inventory') }}" class="m-nav-link {{ request()->routeIs('merchant.inventory') ? 'active' : '' }}">
            <i class="bi bi-boxes"></i>
            <span>Produk</span>
        </a>
        <a href="{{ route('merchant.finance') }}" class="m-nav-link {{ request()->routeIs('merchant.finance') ? 'active' : '' }}">
            <i class="bi bi-cash-coin"></i>
            <span>Keuangan</span>
        </a>
        <a href="{{ route('merchant.profile') }}" class="m-nav-link {{ request()->routeIs('merchant.profile') ? 'active' : '' }}">
            <i class="bi bi-shop"></i>
            <span>Profil</span>
        </a>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
