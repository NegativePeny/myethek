<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#10b981">
    <meta name="description" content="Mak Ethek - Platform Belanja Kebutuhan Harian Jaringan Koperasi Jawa Timur">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <title>@yield('title', 'Mak Ethek - Belanja Kebutuhan Harian Desa')</title>

    <!-- PWA Manifest & App Icons -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

    <!-- Slick Slider -->
    <link rel="stylesheet" type="text/css" href="/grofar/vendor/slick/slick.min.css"/>
    <link rel="stylesheet" type="text/css" href="/grofar/vendor/slick/slick-theme.min.css"/>
    <!-- Icofont Icon-->
    <link href="/grofar/vendor/icons/icofont.min.css" rel="stylesheet" type="text/css">
    <!-- Bootstrap core CSS -->
    <link href="/grofar/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons Fallback -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Grofar Custom styles -->
    <link href="/grofar/css/style.css" rel="stylesheet">
    <!-- Sidebar CSS -->
    <link href="/grofar/vendor/sidebar/demo.css" rel="stylesheet">

    <style>
        :root {
            --primary-green: #10b981;
            --primary-dark: #059669;
            --surface-green: #ecfdf5;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            background-color: #ffffff !important;
            color: #212529;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        main,
        .osahan-body,
        .osahan-cart,
        .osahan-product {
            background-color: #ffffff !important;
        }

        /* Centered Mobile App Shell */
        .app-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            background: #ffffff;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.08);
            position: relative;
            display: flex;
            flex-direction: column;
            padding-bottom: 74px; /* Space for bottom nav */
        }

        /* Fixed elements alignment for centered mobile container */
        .osahan-menu-fotter.fixed-bottom {
            max-width: 480px;
            left: 50% !important;
            transform: translateX(-50%);
            width: 100%;
            z-index: 1030;
            background: #ffffff;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .fixed-bottom-action {
            position: fixed;
            bottom: 60px;
            max-width: 480px;
            left: 50% !important;
            transform: translateX(-50%);
            width: 100%;
            z-index: 1025;
            padding: 10px 16px;
        }

        .cart-badge-count {
            position: absolute;
            top: 2px;
            right: 28%;
            background: #ef4444;
            color: #ffffff;
            font-size: 0.65rem;
            font-weight: 700;
            border-radius: 9999px;
            padding: 1px 6px;
            line-height: 1.2;
        }

        /* Fallback Banner */
        .fallback-banner {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 10px 14px;
            font-size: 0.82rem;
            margin: 12px 16px 4px;
            border-radius: 6px;
            color: #1e40af;
        }

        .badge-sub-agent {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            font-weight: 600;
        }

        .badge-agent {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            font-weight: 600;
        }

        .badge-distributor {
            background-color: #f3e8ff;
            color: #6b21a8;
            border: 1px solid #e9d5ff;
            font-weight: 600;
        }

        /* Bottom navigation fix: cegah efek samping trigger hc-offcanvas-nav */
        .osahan-menu-fotter .hc-nav-trigger {
            font-size: inherit !important;
            line-height: inherit !important;
        }
        .osahan-menu-fotter a span:not(.cart-badge-count) {
            position: static !important;
            left: auto !important;
            display: inline-block !important;
            font-size: 0.75rem !important;
            line-height: 1.2 !important;
            background: transparent !important;
            height: auto !important;
            border-radius: 0 !important;
            transition: none !important;
        }
        .osahan-menu-fotter .hc-nav-trigger span::before,
        .osahan-menu-fotter .hc-nav-trigger span::after {
            display: none !important;
            content: none !important;
        }

        /* Input group & form control styling fix (override Grofar underline style) */
        .input-group .form-control:not(.border-0) {
            border: 1px solid #ced4da;
            background-color: #ffffff;
            border-radius: 0.375rem;
        }
        .input-group .form-control.border-start-0 {
            border-left: 0 !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }
        .input-group .input-group-text.border-end-0 {
            border-color: #ced4da;
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
        .input-group:focus-within .input-group-text.border-end-0 {
            border-color: #10b981;
        }
        .input-group .form-control:focus {
            border-color: #10b981;
            box-shadow: none;
        }
        textarea.form-control:not(.border-0) {
            border: 1px solid #ced4da;
            background-color: #ffffff;
            border-radius: 0.375rem;
        }
        textarea.form-control:focus {
            border-color: #10b981;
            box-shadow: none;
        }

        /* Bootstrap subtle utilities fallback for badge readability */
        .bg-warning-subtle { background-color: #fef3c7 !important; }
        .text-warning-emphasis { color: #92400e !important; }
        .bg-success-subtle { background-color: #dcfce7 !important; }
        .text-success-emphasis { color: #166534 !important; }
        .bg-info-subtle { background-color: #e0f2fe !important; }
        .text-info-emphasis { color: #0369a1 !important; }
        .bg-danger-subtle { background-color: #fee2e2 !important; }
        .text-danger-emphasis { color: #991b1b !important; }
        .badge.text-warning-emphasis, .badge.text-success, .badge.text-primary, .badge.text-danger, .badge.text-info {
            font-weight: 600;
        }
    </style>
    @yield('styles')
</head>
<body class="fixed-bottom-padding">

<div class="app-container" id="container">
    @php
        $currentTerritory = $currentTerritory ?? null;
        $resolution = $resolution ?? [];
        $globalCartCount = app(\App\Services\CartService::class)->getItemCount();
    @endphp

    <!-- Top Header -->
    <div class="border-bottom p-3 bg-white sticky-top shadow-sm" style="z-index: 1020;">
        <div class="title d-flex align-items-center">
            <a href="{{ route('storefront.index') }}" class="text-decoration-none text-dark d-flex align-items-center">
                <img class="osahan-logo me-2" src="/grofar/img/logo.png" style="height: 38px; width: 38px; object-fit: contain;" alt="Logo Mak Ethek">
                <div>
                    <h5 class="fw-bold text-success m-0" style="line-height: 1.1;">Mak Ethek</h5>
                    <span class="text-muted" style="font-size: 0.65rem; letter-spacing: 0.5px;">KOPERASI JATIM</span>
                </div>
            </a>
            
            <div class="ms-auto d-flex align-items-center gap-2">
                <!-- Location Pill -->
                <a href="#" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-truncate d-flex align-items-center gap-1" 
                   style="max-width: 145px; font-size: 0.72rem;" 
                   data-bs-toggle="modal" data-bs-target="#locationModal" 
                   title="Ganti Wilayah">
                    <i class="icofont-location-pin text-danger"></i>
                    <span class="text-truncate fw-semibold">{{ $currentTerritory?->name ?? 'Pilih Desa' }}</span>
                </a>

                @auth
                    <a href="{{ auth()->user()->isMerchant() ? route('merchant.index') : route('storefront.index') }}" 
                       class="btn btn-sm btn-success rounded-circle d-flex align-items-center justify-content-center p-0 text-white" 
                       style="width: 28px; height: 28px;" 
                       title="{{ auth()->user()->name }} ({{ strtoupper(auth()->user()->role) }})">
                        <i class="icofont-user" style="font-size: 0.85rem;"></i>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-success rounded-pill px-2 py-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                        <i class="icofont-login"></i>
                        <span>Masuk</span>
                    </a>
                @endauth

                <!-- Hamburger Drawer Toggle -->
                <a class="toggle text-dark fs-5 text-decoration-none p-1" href="#" role="button" aria-label="Toggle Navigation">
                    <i class="icofont-navigation-menu"></i>
                </a>
            </div>
        </div>

        @yield('header_extra')
    </div>

    <!-- Main Content Area -->
    <main class="flex-grow-1">
        @if(session('location_updated'))
            <div class="alert alert-success alert-dismissible fade show mx-3 my-2 py-2 px-3 small" role="alert">
                <i class="icofont-check-circled me-1"></i> {{ session('location_updated') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mx-3 my-2 py-2 px-3 small" role="alert">
                <i class="icofont-check-circled me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mx-3 my-2 py-2 px-3 small" role="alert">
                <i class="icofont-warning-alt me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Bottom Navigation Bar (Grofar Style) -->
    <div class="osahan-menu-fotter fixed-bottom bg-white text-center border-top">
        <div class="row m-0 bg-white">
            <a href="{{ route('storefront.index') }}" class="col small p-2 text-decoration-none bg-white {{ request()->routeIs('storefront.index') ? 'text-success fw-bold' : 'text-muted' }}">
                <p class="h5 m-0"><i class="icofont-grocery {{ request()->routeIs('storefront.index') ? 'text-success' : '' }}"></i></p>
                <span>Belanja</span>
            </a>

            <a href="{{ auth()->check() ? route('user.orders.index') : route('login') }}" class="col small p-2 text-decoration-none bg-white {{ request()->routeIs('user.orders.*') ? 'text-success fw-bold' : 'text-muted' }}">
                <p class="h5 m-0"><i class="icofont-bag {{ request()->routeIs('user.orders.*') ? 'text-success' : '' }}"></i></p>
                <span>Pesanan</span>
            </a>
            
            <a href="{{ route('storefront.cart') }}" class="col small p-2 text-decoration-none bg-white position-relative {{ request()->routeIs('storefront.cart') ? 'text-success fw-bold' : 'text-muted' }}">
                <p class="h5 m-0"><i class="icofont-cart {{ request()->routeIs('storefront.cart') ? 'text-success' : '' }}"></i></p>
                <span>Keranjang</span>
                @if($globalCartCount > 0)
                    <span class="cart-badge-count">{{ $globalCartCount }}</span>
                @endif
            </a>

            <a href="{{ auth()->check() ? route('user.profile') : route('login') }}" class="col small p-2 text-decoration-none bg-white {{ request()->routeIs('user.profile') ? 'text-success fw-bold' : 'text-muted' }}">
                <p class="h5 m-0"><i class="icofont-user {{ request()->routeIs('user.profile') ? 'text-success' : '' }}"></i></p>
                <span>Akun</span>
            </a>
        </div>
    </div>
</div>

<!-- Offcanvas Sidebar Menu (#main-nav) -->
<nav id="main-nav">
    <ul class="second-nav">
        <li class="p-3 bg-light border-bottom">
            @auth
                <div class="d-flex align-items-center">
                    <div class="bg-success text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="icofont-user fs-5"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 fw-bold text-dark text-truncate">{{ auth()->user()->name }}</h6>
                        <small class="text-muted d-block text-truncate">{{ auth()->user()->phone ?? auth()->user()->email }}</small>
                        <span class="badge bg-success-subtle text-success border border-success mt-1" style="font-size: 0.65rem;">
                            {{ strtoupper(auth()->user()->role) }}
                        </span>
                    </div>
                </div>
            @else
                <div class="d-flex align-items-center">
                    <img src="/grofar/img/logo.png" style="height: 38px; width: 38px; object-fit: contain;" class="me-2" alt="Logo Mak Ethek">
                    <div>
                        <h6 class="mb-0 fw-bold text-success">Mak Ethek</h6>
                        <small class="text-muted">Koperasi Jatim Retail</small>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2 pt-2 border-top">
                    <a href="{{ route('login') }}" class="btn btn-sm btn-success flex-fill fw-bold py-1" style="font-size: 0.75rem;">
                        <i class="icofont-login me-1"></i> Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-outline-secondary flex-fill py-1" style="font-size: 0.75rem;">
                        Daftar
                    </a>
                </div>
            @endauth
        </li>
        <li>
            <a href="{{ route('storefront.index') }}">
                <i class="icofont-home me-2"></i> Beranda Belanja
            </a>
        </li>
        <li>
            <a href="{{ route('storefront.cart') }}">
                <i class="icofont-cart me-2"></i> Keranjang Belanja 
                @if($globalCartCount > 0)
                    <span class="badge bg-success float-end">{{ $globalCartCount }}</span>
                @endif
            </a>
        </li>
        <li>
            <a href="#" data-bs-toggle="modal" data-bs-target="#locationModal">
                <i class="icofont-location-pin me-2"></i> Ganti Desa / Lokasi Antar
            </a>
        </li>
        <li>
            <a href="#">
                <i class="icofont-building me-2"></i> Ekosistem & Portal Mitra
            </a>
            <ul>
                <li><a href="{{ route('merchant.index') }}"><i class="icofont-shop me-2"></i> Portal Mitra Warung (B2B)</a></li>
                <li><a href="{{ route('driver.index') }}"><i class="icofont-delivery-time me-2"></i> Portal Driver Ojol Desa</a></li>
                <li><a href="{{ route('admin.index') }}"><i class="icofont-chart-bar-graph me-2"></i> Audit Holding Koperasi</a></li>
                <li><a href="{{ route('admin.products.index') }}"><i class="icofont-box me-2"></i> Master Katalog Produk</a></li>
            </ul>
        </li>
        <li>
            <a href="https://wa.me/628120000001?text=Halo%20Admin%20Mak%20Ethek,%20saya%20butuh%20bantuan" target="_blank">
                <i class="icofont-headphone-alt me-2"></i> Bantuan & Kontak Pengurus
            </a>
        </li>
        @auth
        <li class="border-top p-2">
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger w-100 text-start py-2 px-3 fw-semibold border-0" style="font-size: 0.85rem;">
                    <i class="icofont-logout me-2"></i> Keluar (Logout)
                </button>
            </form>
        </li>
        @endauth
    </ul>
</nav>

<!-- Modal Pemilih Lokasi / Desa -->
@include('storefront.partials.location-modal')

<!-- Core Javascript Dependencies -->
<script src="/grofar/vendor/jquery/jquery.min.js"></script>
<script src="/grofar/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/grofar/vendor/slick/slick.min.js"></script>
<script src="/grofar/vendor/sidebar/hc-offcanvas-nav.js"></script>
<script src="/grofar/js/osahan.js"></script>
<script>
    $(document).ready(function() {
        $('#bottomNavMenuToggle').on('click', function(e) {
            e.preventDefault();
            $('a.toggle').first().trigger('click');
        });
    });
</script>

<!-- PWA Service Worker Registration -->
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('PWA ServiceWorker registered:', reg.scope))
                .catch(err => console.log('PWA ServiceWorker registration failed:', err));
        });
    }
</script>

@yield('scripts')
</body>
</html>
