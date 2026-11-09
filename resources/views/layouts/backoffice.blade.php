<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f172a">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <title>@yield('title', 'Backoffice Web Desktop - Mak Ethek Kota Blitar')</title>

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 268px;
            --primary-emerald: #059669;
            --dark-slate: #0f172a;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            margin: 0;
            overflow-x: hidden;
        }

        .desktop-shell {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar Web Desktop */
        .desktop-sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            background-color: var(--sidebar-bg);
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1030;
        }

        .sidebar-brand {
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-section-title {
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            font-weight: 700;
            padding: 18px 20px 6px;
        }

        .sidebar-nav-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 20px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.86rem;
            font-weight: 500;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-nav-link:hover {
            background-color: var(--sidebar-hover);
            color: #ffffff;
        }

        .sidebar-nav-link.active {
            background-color: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border-left-color: #10b981;
            font-weight: 600;
        }

        .sidebar-nav-link i {
            font-size: 1.05rem;
        }

        /* Main Area Web Desktop */
        .desktop-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .desktop-topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .desktop-content {
            padding: 24px 28px 48px;
            flex: 1;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        @media (max-width: 991.98px) {
            .desktop-shell {
                flex-direction: column;
            }
            .desktop-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="desktop-shell">
    <!-- Sidebar Kiri (Tampilan Web Desktop) -->
    <aside class="desktop-sidebar">
        <div class="sidebar-brand">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-success px-2 py-1 fw-bold" style="font-size: 0.65rem;">WEB DESKTOP</span>
                <span class="badge bg-secondary bg-opacity-25 text-light" style="font-size: 0.65rem;">PRD/SOT v1.0</span>
            </div>
            <h5 class="fw-bold mb-0 text-white d-flex align-items-center gap-2">
                <i class="bi bi-shop-window text-success"></i> MAK ETHEK
            </h5>
            <small class="text-secondary d-block mt-1" style="font-size: 0.74rem;">
                Pilot Kota Blitar • 1 Koperasi & 1 Gudang
            </small>
        </div>

        <!-- Menu Super Admin & Finance -->
        <div class="sidebar-section-title">Super Admin & Finance</div>
        <a href="{{ route('finance.index') }}" class="sidebar-nav-link {{ request()->routeIs('finance.*') || request()->routeIs('superadmin.*') ? 'active' : '' }}">
            <i class="bi bi-pie-chart-fill"></i>
            <span>KPI Eksekutif & Keuangan</span>
        </a>
        <a href="{{ route('finance.index') }}#section-doku" class="sidebar-nav-link">
            <i class="bi bi-shield-check"></i>
            <span>Rekonsiliasi DOKU & Refund</span>
        </a>
        <a href="{{ route('finance.index') }}#section-b2b" class="sidebar-nav-link">
            <i class="bi bi-building-check"></i>
            <span>Verifikasi Akun B2B (Rp1 Jt)</span>
        </a>
        <a href="{{ route('finance.index') }}#section-audit" class="sidebar-nav-link">
            <i class="bi bi-journal-code"></i>
            <span>Audit Trail & Keamanan</span>
        </a>

        <!-- Menu Admin Gudang & Operasional Koperasi -->
        <div class="sidebar-section-title">Admin Gudang & Koperasi</div>
        <a href="{{ route('warehouse.index') }}" class="sidebar-nav-link {{ request()->routeIs('warehouse.*') && request('tab', 'picking') === 'picking' ? 'active' : '' }}">
            <i class="bi bi-box-seam-fill"></i>
            <span>WMS Picking, Packing & Handover</span>
        </a>
        <a href="{{ route('warehouse.index', ['tab' => 'inventory']) }}" class="sidebar-nav-link {{ request()->routeIs('warehouse.*') && request('tab') === 'inventory' ? 'active' : '' }}">
            <i class="bi bi-boxes"></i>
            <span>Stok Gudang & Stock Movement</span>
        </a>
        <a href="{{ route('admin.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}">
            <i class="bi bi-clipboard2-data-fill"></i>
            <span>Manajemen Order & Dispatch</span>
        </a>
        <a href="{{ route('admin.products.index') }}" class="sidebar-nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
            <i class="bi bi-tags-fill"></i>
            <span>Master SKU & Harga B2C/B2B</span>
        </a>

        <!-- Pintasan Pratinjau Tampilan Mobile -->
        <div class="sidebar-section-title">Akses Aplikasi Mobile</div>
        <a href="{{ route('storefront.index') }}" class="sidebar-nav-link">
            <i class="bi bi-phone-fill text-info"></i>
            <span>Tampilan Mobile User (PWA)</span>
        </a>
        <a href="{{ route('driver.index') }}" class="sidebar-nav-link">
            <i class="bi bi-scooter text-warning"></i>
            <span>Tampilan Mobile Driver KurirQ</span>
        </a>

        <div class="mt-auto p-3 border-top border-secondary border-opacity-25">
            @auth
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-truncate me-2">
                        <div class="fw-semibold small text-white text-truncate">{{ auth()->user()->name }}</div>
                        <span class="badge bg-emerald bg-success bg-opacity-25 text-success border border-success border-opacity-25" style="font-size: 0.65rem;">
                            ROLE: {{ strtoupper(auth()->user()->role) }}
                        </span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light" title="Keluar">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </aside>

    <!-- Area Konten Utama Web Desktop -->
    <div class="desktop-main">
        <!-- Topbar Desktop -->
        <header class="desktop-topbar">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">@yield('page_heading', 'Portal Manajemen Koperasi Mak Ethek — Kota Blitar')</h6>
                    <small class="text-muted" style="font-size: 0.76rem;">
                        @yield('page_subheading', 'Satu Koperasi • Satu Merchant Utama • Satu Gudang/Toko • Integrasi DOKU & KurirQ')
                    </small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if(app()->environment(['local', 'testing']))
                    <!-- Quick Demo Role Switcher untuk memudahkan pengujian Web vs Mobile -->
                    <form action="{{ route('demo.switchRole') }}" method="POST" class="d-flex align-items-center gap-1">
                        @csrf
                        <span class="badge bg-light text-secondary border"><i class="bi bi-person-gear me-1"></i>Simulasi Peran:</span>
                        <select name="email" class="form-select form-select-sm" style="width: auto; font-size: 0.78rem;" onchange="this.form.submit()">
                            <option value="superadmin@myethek.id" {{ auth()->user()?->email === 'superadmin@myethek.id' ? 'selected' : '' }}>
                                [WEB] Super Admin (superadmin@myethek.id)
                            </option>
                            <option value="finance@myethek.id" {{ auth()->user()?->email === 'finance@myethek.id' ? 'selected' : '' }}>
                                [WEB] Finance & DOKU (finance@myethek.id)
                            </option>
                            <option value="gudang@myethek.id" {{ auth()->user()?->email === 'gudang@myethek.id' ? 'selected' : '' }}>
                                [WEB] Admin Gudang (gudang@myethek.id)
                            </option>
                            <option value="admin@myethek.id" {{ auth()->user()?->email === 'admin@myethek.id' ? 'selected' : '' }}>
                                [WEB] Admin Koperasi (admin@myethek.id)
                            </option>
                            <option value="driver.gedog@myethek.id" {{ auth()->user()?->email === 'driver.gedog@myethek.id' ? 'selected' : '' }}>
                                [MOBILE] Driver KurirQ (driver.gedog@myethek.id)
                            </option>
                            <option value="customer.gedog@myethek.id" {{ auth()->user()?->email === 'customer.gedog@myethek.id' ? 'selected' : '' }}>
                                [MOBILE] User Retail B2C (customer.gedog@myethek.id)
                            </option>
                            <option value="warung.b2b@myethek.id" {{ auth()->user()?->email === 'warung.b2b@myethek.id' ? 'selected' : '' }}>
                                [MOBILE] User Grosir B2B (warung.b2b@myethek.id)
                            </option>
                        </select>
                    </form>
                @endif
            </div>
        </header>

        <!-- Isi Halaman Desktop -->
        <main class="desktop-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
                    <div class="fw-bold mb-1"><i class="bi bi-x-octagon-fill me-1"></i>Terdapat kesalahan input:</div>
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>

