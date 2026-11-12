@extends('layouts.storefront')

@section('title', 'Detail Pesanan #' . $order->order_number . ' - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('user.orders.index') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
                <i class="icofont-rounded-left"></i> Pesanan
            </a>
            <span class="fw-bold small text-dark">
                #{{ $order->order_number }}
            </span>
        </div>
        <span class="text-muted" style="font-size: 0.72rem;">
            {{ $order->created_at->translatedFormat('d M Y, H:i') }}
        </span>
    </div>
@endsection

@section('styles')
    <!-- Leaflet.js CSS (Free OpenStreetMap) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        .tracking-map-wrapper {
            position: relative;
            width: 100%;
            height: 290px;
            border-radius: 12px;
            overflow: hidden;
            z-index: 1;
            background-color: #f1f5f9;
        }
        #liveTrackingMap {
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        .custom-map-icon {
            background: none !important;
            border: none !important;
        }
        .custom-map-pin {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.35);
            color: #ffffff;
            font-size: 16px;
            border: 2.5px solid #ffffff;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .custom-map-pin:hover {
            transform: scale(1.1);
        }
        .pin-store {
            background: linear-gradient(135deg, #10b981, #059669);
            width: 36px;
            height: 36px;
        }
        .pin-driver {
            background: linear-gradient(135deg, #0284c7, #0369a1);
            width: 42px;
            height: 42px;
            position: relative;
        }
        .driver-icon-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            transition: transform 0.4s ease-out;
        }
        .custom-driver-marker {
            transition: transform 0.8s cubic-bezier(0.25, 1, 0.5, 1);
        }
        .pin-driver::after {
            content: '';
            position: absolute;
            top: -6px;
            left: -6px;
            right: -6px;
            bottom: -6px;
            border-radius: 50%;
            border: 2.5px solid #0284c7;
            opacity: 0.8;
            animation: pulse-driver 1.8s cubic-bezier(0.24, 0, 0.38, 1) infinite;
        }
        @keyframes pulse-driver {
            0% { transform: scale(0.9); opacity: 0.9; }
            70% { transform: scale(1.6); opacity: 0.15; }
            100% { transform: scale(1.8); opacity: 0; }
        }
        .pin-dest {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            width: 36px;
            height: 36px;
        }
        .leaflet-popup-content-wrapper {
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.18);
            font-size: 0.78rem;
        }
    </style>
@endsection

@section('content')
<div class="osahan-status p-3">
    @php
        $status = strtolower($order->status ?? 'pending');
        $packingStatus = strtolower($order->packing_status ?? 'not_started');
        $deliveryStatus = strtolower($order->delivery->status ?? 'pending');
        $isSelfPickup = ($order->delivery?->delivery_type === 'self_pickup');

        if (in_array($status, ['cancelled', 'refunded', 'expired', 'failed'])) {
            $currentStep = 0;
            $statusTitle = 'Pesanan Dibatalkan';
            $statusDesc = $order->notes ? $order->notes : 'Pesanan ini telah dibatalkan atau kedaluwarsa.';
            $badgeColor = 'danger';
            $badgeIcon = 'close-circled';
            $badgeText = 'Batal';
        } elseif (in_array($status, ['delivered', 'completed']) || in_array($deliveryStatus, ['delivered'])) {
            $currentStep = 4;
            $statusTitle = 'Pesanan Selesai';
            $statusDesc = $isSelfPickup 
                ? 'Barang pesanan telah berhasil diambil dan diserahkan di lokasi toko/gudang koperasi.' 
                : 'Barang belanjaan telah berhasil diterima pemesan dengan verifikasi serah-terima OTP.';
            $badgeColor = 'success';
            $badgeIcon = 'check-circled';
            $badgeText = 'Selesai';
        } elseif (
            in_array($status, ['on_delivery', 'shipping', 'shipped', 'picked_up']) ||
            in_array($deliveryStatus, ['on_delivery', 'picked_up']) ||
            ($packingStatus === 'handed_over' && in_array($deliveryStatus, ['assigned', 'accepted'])) ||
            in_array($status, ['assigned']) ||
            ($isSelfPickup && ($status === 'ready_for_pickup' || $packingStatus === 'packed' || $packingStatus === 'handed_over'))
        ) {
            $currentStep = 3;
            $badgeColor = 'primary';
            $badgeIcon = 'refresh';
            if ($isSelfPickup) {
                $statusTitle = 'Siap Diambil di Gudang/Toko';
                $statusDesc = 'Pesanan sudah selesai dikemas dan siap Anda ambil langsung di lokasi gudang koperasi.';
                $badgeText = 'Siap Diambil';
            } elseif (in_array($deliveryStatus, ['on_delivery', 'picked_up']) || in_array($status, ['on_delivery', 'shipping', 'shipped'])) {
                $statusTitle = 'Kurir Sedang Mengantar';
                $statusDesc = 'Kurir KurirQ sedang dalam perjalanan mengantarkan paket ke alamat Anda.';
                $badgeText = 'Sedang Diantar';
            } else {
                $statusTitle = 'Paket Diserahkan ke Kurir';
                $statusDesc = 'Pesanan telah selesai dikemas dan diserahkan ke Driver KurirQ untuk pengantaran.';
                $badgeText = 'Menunggu Kurir';
            }
        } elseif (
            in_array($status, ['processing', 'picking', 'packed', 'ready_for_pickup', 'confirmed', 'accepted']) ||
            in_array($packingStatus, ['picking', 'packed']) ||
            ($status === 'paid')
        ) {
            $currentStep = 2;
            $badgeColor = 'primary';
            $badgeIcon = 'refresh';
            $badgeText = 'Sedang Disiapkan';
            if ($packingStatus === 'packed' || $status === 'ready_for_pickup') {
                $statusTitle = 'Selesai Dikemas Gudang';
                $statusDesc = 'Pesanan Anda telah selesai dikemas oleh petugas gudang dan siap serah-terima pengiriman.';
            } elseif ($packingStatus === 'picking' || $status === 'picking') {
                $statusTitle = 'Sedang Di-pick di Gudang';
                $statusDesc = 'Petugas gudang koperasi sedang mengambil barang sesuai daftar belanjaan Anda.';
            } else {
                $statusTitle = 'Pesanan Sedang Disiapkan';
                $statusDesc = 'Toko/Gudang mitra telah menerima pesanan dan sedang menyiapkan barang.';
            }
        } else {
            // Default: pending / baru checkout
            $currentStep = 1;
            $badgeColor = 'warning';
            $badgeIcon = 'clock-time';
            $badgeText = 'Menunggu';
            $statusTitle = $order->payment_status === 'paid' ? 'Pesanan Diterima (Lunas)' : 'Menunggu Konfirmasi / Pembayaran';
            $statusDesc = 'Pesanan Anda telah diterima sistem dan masuk ke antrean gudang koperasi.';
        }

        // Tentukan persentase garis progress bar
        $progressPct = match($currentStep) {
            1 => 0,
            2 => 33,
            3 => 66,
            4 => 100,
            default => 0,
        };
    @endphp

    <!-- Card Status Tracking -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold text-dark m-0">{{ $statusTitle }}</h6>
                <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }} px-2 py-1" style="font-size: 0.7rem;">
                    <i class="icofont-{{ $badgeIcon }} me-1"></i> {{ $badgeText }}
                </span>
            </div>
            <p class="text-muted small mb-3">{{ $statusDesc }}</p>

            @if($order->status !== 'cancelled')
                <!-- Stepper Visual dengan Connecting Line & Centang Jelas -->
                <div class="position-relative my-3 px-2">
                    <!-- Background Connecting Line -->
                    <div class="position-absolute" style="top: 15px; left: 12%; right: 12%; height: 3px; background-color: #e2e8f0; z-index: 1;">
                        <!-- Active Progress Line -->
                        <div class="bg-success h-100 transition-all" style="width: {{ $progressPct }}%; transition: width 0.3s ease;"></div>
                    </div>

                    <div class="d-flex justify-content-between position-relative" style="z-index: 2;">
                        <!-- Step 1: Diterima -->
                        <div class="text-center" style="width: 25%;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 {{ $currentStep >= 1 ? 'bg-success text-white shadow-sm' : 'bg-light text-muted border' }}" 
                                 style="width: 30px; height: 30px; font-size: 0.85rem; font-weight: 700;">
                                <i class="icofont-check"></i>
                            </div>
                            <span class="d-block text-truncate small {{ $currentStep >= 1 ? 'fw-bold text-success' : 'text-muted' }}" style="font-size: 0.68rem;">Diterima</span>
                        </div>

                        <!-- Step 2: Disiapkan -->
                        <div class="text-center" style="width: 25%;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 {{ $currentStep >= 2 ? 'bg-success text-white shadow-sm' : 'bg-light text-muted border' }}" 
                                 style="width: 30px; height: 30px; font-size: 0.85rem; font-weight: 700;">
                                @if($currentStep >= 2)
                                    <i class="icofont-check"></i>
                                @else
                                    2
                                @endif
                            </div>
                            <span class="d-block text-truncate small {{ $currentStep >= 2 ? 'fw-bold text-success' : 'text-muted' }}" style="font-size: 0.68rem;">Disiapkan</span>
                        </div>

                        <!-- Step 3: Diantar / Siap Ambil -->
                        <div class="text-center" style="width: 25%;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 {{ $currentStep >= 3 ? 'bg-success text-white shadow-sm' : 'bg-light text-muted border' }}" 
                                 style="width: 30px; height: 30px; font-size: 0.85rem; font-weight: 700;">
                                @if($currentStep >= 3)
                                    <i class="icofont-check"></i>
                                @else
                                    3
                                @endif
                            </div>
                            <span class="d-block text-truncate small {{ $currentStep >= 3 ? 'fw-bold text-success' : 'text-muted' }}" style="font-size: 0.68rem;">
                                {{ $isSelfPickup ? 'Siap Ambil' : 'Diantar' }}
                            </span>
                        </div>

                        <!-- Step 4: Selesai -->
                        <div class="text-center" style="width: 25%;">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1 {{ $currentStep >= 4 ? 'bg-success text-white shadow-sm' : 'bg-light text-muted border' }}" 
                                 style="width: 30px; height: 30px; font-size: 0.85rem; font-weight: 700;">
                                @if($currentStep >= 4)
                                    <i class="icofont-check"></i>
                                @else
                                    4
                                @endif
                            </div>
                            <span class="d-block text-truncate small {{ $currentStep >= 4 ? 'fw-bold text-success' : 'text-muted' }}" style="font-size: 0.68rem;">Selesai</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Live Map Tracking Leaflet (OSM Gratis) -->
    @if($order->status !== 'cancelled')
        @php
            $delivStatus = strtolower($order->delivery->status ?? 'pending');
            $driverName = $order->delivery?->driver?->name ?? 'Kurir Mitra Koperasi';
            $driverPhone = $order->delivery?->driver?->phone;
            $cleanDriverPhone = preg_replace('/[^0-9]/', '', $driverPhone ?? '');
            if (str_starts_with($cleanDriverPhone, '0')) {
                $cleanDriverPhone = '62' . substr($cleanDriverPhone, 1);
            }
            $initialDistance = $order->delivery?->distance_km ?? 0;
            $initialEstMinutes = max(3, (int) round(($initialDistance > 0 ? $initialDistance : 1) * 3.5));
        @endphp
        <div class="card border rounded-3 shadow-sm bg-white mb-3 overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm text-success" role="status" id="livePingDot" style="width: 10px; height: 10px;"></span>
                    <div>
                        <h6 class="fw-bold small text-dark mb-0">
                            <i class="icofont-google-map text-success me-1"></i> 
                            {{ $isSelfPickup ? 'Titik Pengambilan di Toko' : 'Live Tracking KurirQ' }}
                        </h6>
                        <small class="text-muted" style="font-size: 0.68rem;" id="trackingHeaderSubtitle">
                            {{ $isSelfPickup ? 'Koperasi Konsumen Kota Blitar' : 'Pemantauan Posisi Kurir Real-Time' }}
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-muted" id="btnManualRefresh" style="font-size: 0.7rem;">
                        <i class="icofont-refresh me-1" id="refreshIcon"></i> <span id="refreshLabel">Sync</span>
                    </button>
                </div>
            </div>

            <!-- Map Container -->
            <div class="tracking-map-wrapper">
                <div id="liveTrackingMap"></div>

                <!-- Floating Live Distance & Time Card -->
                @if(!$isSelfPickup)
                    <div class="position-absolute bottom-0 start-0 end-0 p-2" style="z-index: 500; pointer-events: none;">
                        <div class="bg-white rounded-3 shadow p-2 border d-flex align-items-center justify-content-between" style="pointer-events: auto; backdrop-filter: blur(10px); background-color: rgba(255,255,255,0.94);">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.1rem;">
                                    🛵
                                </div>
                                <div>
                                    <span class="d-block fw-bold text-dark" style="font-size: 0.78rem;" id="trackingDriverName">
                                        {{ $driverName }}
                                    </span>
                                    <span class="d-block text-muted" style="font-size: 0.68rem;" id="trackingStatusText">
                                        @if(in_array($delivStatus, ['on_delivery', 'picked_up']))
                                            Sedang meluncur ke alamat Anda
                                        @elseif($delivStatus === 'delivered')
                                            Paket telah tiba di tujuan
                                        @elseif(in_array($delivStatus, ['assigned', 'accepted']))
                                            Kurir menuju gudang pickup
                                        @else
                                            Menunggu penugasan kurir
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="text-end">
                                    <span class="badge bg-success fw-bold d-inline-block" style="font-size: 0.75rem;" id="trackingDistanceBadge">
                                        {{ $initialDistance > 0 ? $initialDistance . ' km' : 'Menghitung...' }}
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 0.65rem;" id="trackingTimeEst">
                                        ± {{ $initialEstMinutes }} mnt
                                    </small>
                                </div>
                                @if($cleanDriverPhone)
                                    <a href="https://wa.me/{{ $cleanDriverPhone }}?text=Halo%20{{ urlencode($driverName) }},%20saya%20pemesan%20nomor%20{{ $order->order_number }}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                                       style="width: 32px; height: 32px; padding: 0;" title="Chat WhatsApp Kurir">
                                        <i class="icofont-brand-whatsapp fs-6"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="position-absolute bottom-0 start-0 end-0 p-2" style="z-index: 500; pointer-events: none;">
                        <div class="bg-white rounded-3 shadow p-2 border d-flex align-items-center justify-content-between" style="pointer-events: auto;">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.68rem;">Titik Pengambilan:</small>
                                <span class="fw-bold small text-dark d-block">{{ $order->merchant?->store_name }}</span>
                            </div>
                            @php
                                $originLat = (float) ($order->merchant->latitude ?? -8.098254);
                                $originLon = (float) ($order->merchant->longitude ?? 112.168153);
                            @endphp
                            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $originLat }},{{ $originLon }}" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-bold" style="font-size: 0.72rem;">
                                <i class="icofont-direction-sign me-1"></i> Buka Rute
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Footer Live Info -->
            <div class="card-footer bg-light p-2 border-top">
                <div class="d-flex align-items-center justify-content-between" style="font-size: 0.7rem;">
                    <span class="text-muted d-flex align-items-center gap-1">
                        <i class="icofont-clock-time text-secondary"></i>
                        <span id="lastUpdatedTime">Update: Baru saja</span>
                    </span>
                    @if(!$isSelfPickup && in_array($delivStatus, ['assigned', 'accepted', 'picked_up', 'on_delivery']))
                        <span class="badge bg-success-subtle text-success border border-success" style="font-size: 0.65rem;">
                            <i class="icofont-refresh me-1"></i> Auto-Sync Tiap 8 Detik
                        </span>
                    @elseif($delivStatus === 'delivered')
                        <span class="badge bg-success text-white" style="font-size: 0.65rem;">
                            <i class="icofont-check-circled me-1"></i> Terkirim Selesai
                        </span>
                    @else
                        <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">
                            OpenStreetMap Leaflet
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Card OTP Serah-Terima Pesanan (Khusus Pelanggan) -->
    @if($order->delivery && $order->delivery->completion_otp && !in_array($order->delivery->status, ['delivered', 'cancelled']))
        @php
            $isCustOtpExpired = $order->delivery->otp_expires_at && now()->greaterThan($order->delivery->otp_expires_at);
        @endphp
        <div class="card border rounded-3 shadow-sm bg-white mb-3 {{ $isCustOtpExpired ? 'border-warning' : 'border-success' }}">
            <div class="card-body p-3 text-center">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-success-subtle text-success border border-success small" style="font-size: 0.7rem;">
                        <i class="icofont-shield-alt me-1"></i> Keamanan Escrow
                    </span>
                    @if($order->delivery->otp_expires_at)
                        <span class="badge {{ $isCustOtpExpired ? 'bg-danger text-white' : 'bg-light text-muted border' }}" style="font-size: 0.68rem;">
                            {{ $isCustOtpExpired ? 'Kedaluwarsa' : 'Aktif s.d ' . $order->delivery->otp_expires_at->format('H:i') }}
                        </span>
                    @endif
                </div>

                <small class="text-muted fw-bold d-block text-uppercase" style="letter-spacing: 1px; font-size: 0.7rem;">
                    KODE OTP SERAH-TERIMA PENGANTARAN:
                </small>

                <div class="my-2 p-2 rounded bg-light border border-2 border-dashed {{ $isCustOtpExpired ? 'border-warning' : 'border-success' }}">
                    <span class="display-6 fw-bold {{ $isCustOtpExpired ? 'text-muted' : 'text-success' }} font-monospace" style="letter-spacing: 4px;">
                        {{ $order->delivery->completion_otp }}
                    </span>
                </div>

                <p class="text-muted small mb-2" style="font-size: 0.72rem;">
                    Berikan 4-digit kode OTP di atas kepada Driver KurirQ saat barang belanjaan tiba di rumah Anda untuk konfirmasi serah-terima.
                </p>

                @if($isCustOtpExpired)
                    <div class="alert alert-warning py-1 px-2 small mb-2" style="font-size: 0.7rem;">
                        <i class="icofont-warning-alt me-1"></i> Kode OTP telah melewati batas 3 jam. Silakan klik tombol di bawah untuk memperbarui OTP.
                    </div>
                @endif

                <form action="{{ route('user.orders.renewOtp', $order->order_number) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fw-bold" style="font-size: 0.75rem;">
                        <i class="icofont-refresh me-1"></i> Perbarui / Minta Kode OTP Baru
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- Toko Penjual -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-2">
                <i class="icofont-shop text-success me-1"></i> Toko Penyedia Belanjaan
            </h6>
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <span class="fw-bold text-dark d-block">{{ $order->merchant?->store_name }}</span>
                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                        {{ $order->merchant?->address }} ({{ $order->merchant?->territory?->name }})
                    </small>
                </div>
                @if($order->merchant?->phone)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->merchant->phone) }}?text=Halo%20{{ urlencode($order->merchant->store_name) }},%20saya%20menanyakan%20pesanan%20nomor%20{{ $order->order_number }}" 
                       target="_blank" 
                       class="btn btn-sm btn-outline-success rounded-pill px-2 py-1 text-nowrap" style="font-size: 0.72rem;">
                        <i class="icofont-brand-whatsapp"></i> Chat Toko
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Info Kurir & Pengiriman -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-2">
                <i class="icofont-delivery-time text-primary me-1"></i> Pengiriman & Kurir
            </h6>
            
            <div class="mb-2 pb-2 border-bottom">
                <small class="text-muted d-block" style="font-size: 0.7rem;">Alamat Pengantaran</small>
                <span class="text-dark small fw-semibold">{{ $order->shipping_address }}</span>
            </div>

            @if($order->delivery)
                <div class="d-flex align-items-center justify-content-between pt-1">
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">Kurir Ojol Desa</small>
                        <span class="fw-bold text-dark small">
                            {{ $order->delivery->driver?->name ?? 'Mencari Kurir Ojol...' }}
                        </span>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                            Kode Lacak: <code>{{ $order->delivery->tracking_code }}</code>
                        </small>
                    </div>

                    @if($order->delivery->driver?->phone)
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->delivery->driver->phone) }}?text=Halo%20Kurir,%20saya%20pemesan%20nomor%20{{ $order->order_number }}" 
                           target="_blank" 
                           class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 text-nowrap" style="font-size: 0.72rem;">
                            <i class="icofont-brand-whatsapp"></i> Hubungi Kurir
                        </a>
                    @endif
                </div>
            @else
                <div class="text-secondary small">
                    <i class="icofont-info-circle me-1"></i> Pengantaran ditangani langsung oleh pihak warung toko.
                </div>
            @endif
        </div>
    </div>

    <!-- Rincian Produk Belanja -->
    <div class="card border rounded-3 shadow-sm bg-white mb-3">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-3">
                <i class="icofont-cart text-success me-1"></i> Rincian Barang ({{ $order->items->count() }} item)
            </h6>

            <div class="d-flex flex-column gap-2">
                @foreach($order->items as $item)
                    @php
                        $prodImg = $item->product?->image;
                        $prodIdx = ($item->product_id % 12) + 1;
                        $fallbackImg = asset("grofar/img/listing/v{$prodIdx}.jpg");
                        $displayImg = $prodImg ? asset($prodImg) : $fallbackImg;
                    @endphp
                    <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $displayImg }}" onerror="this.onerror=null; this.src='{{ $fallbackImg }}';" class="rounded border bg-white" style="width: 40px; height: 40px; object-fit: contain;" alt="{{ $item->product_name }}">
                            <div>
                                <span class="fw-semibold text-dark small d-block">{{ $item->product_name }}</span>
                                <small class="text-muted" style="font-size: 0.72rem;">
                                    {{ $item->quantity }} {{ $item->unit_name }} x Rp {{ number_format($item->price, 0, ',', '.') }}
                                </small>
                            </div>
                        </div>
                        <span class="fw-bold text-dark small">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </span>
                    </div>
                @endforeach
            </div>

            @if($order->notes)
                <div class="mt-2 pt-2 bg-light rounded p-2 small text-secondary">
                    <strong>Catatan Pemesan:</strong> "{{ $order->notes }}"
                </div>
            @endif
        </div>
    </div>

    <!-- Rincian Biaya & Pembayaran -->
    <div class="card border rounded-3 shadow-sm bg-white mb-4">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-3">
                <i class="icofont-bill text-secondary me-1"></i> Rincian Pembayaran
            </h6>

            <div class="d-flex justify-content-between small text-secondary mb-1">
                <span>Subtotal Barang</span>
                <span class="fw-semibold text-dark">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>

            <div class="d-flex justify-content-between small text-secondary mb-1">
                <span>Ongkos Kirim Desa</span>
                <span class="fw-semibold text-dark">Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
            </div>

            <div class="d-flex justify-content-between small text-secondary mb-2">
                <span>Biaya Platform Koperasi</span>
                <span class="fw-semibold text-dark">Rp {{ number_format($order->platform_fee, 0, ',', '.') }}</span>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <div>
                    <span class="fw-bold text-dark">Total Pembayaran</span>
                    <small class="d-block text-muted" style="font-size: 0.72rem;">
                        Metode: <strong>{{ strtoupper($order->payment_method) }}</strong> ({{ $order->payment_status === 'paid' ? 'LUNAS' : 'Belum Lunas / Bayar di Tempat' }})
                    </small>
                </div>
                <span class="fw-bold text-success fs-5">
                    Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Tombol Aksi Bawah -->
    <div class="d-grid gap-2 mb-4">
        @if($order->status === 'pending')
            <button type="button" class="btn btn-outline-danger btn-lg w-100 rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                <i class="icofont-close-circled me-1"></i> Batalkan Pesanan Ini
            </button>

            <!-- Modal Batalkan Pesanan -->
            <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('user.orders.cancel', $order->order_number) }}" method="POST">
                            @csrf
                            <div class="modal-header py-2">
                                <h6 class="modal-title fw-bold text-dark">Batalkan Pesanan #{{ $order->order_number }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-3">
                                <p class="small text-secondary mb-3">
                                    Apakah Anda yakin ingin membatalkan pesanan ini? Stok barang akan segera dikembalikan ke toko mitra.
                                </p>
                                <label class="form-label small fw-semibold text-dark">Pilih Alasan Pembatalan:</label>
                                <select name="cancel_reason" class="form-select form-select-sm mb-2" required>
                                    <option value="Ingin mengubah rincian barang belanjaan">Ingin mengubah rincian barang belanjaan</option>
                                    <option value="Salah memasukkan alamat pengantaran">Salah memasukkan alamat pengantaran</option>
                                    <option value="Ingin mengganti metode pembayaran">Ingin mengganti metode pembayaran</option>
                                    <option value="Berubah pikiran / tidak jadi beli">Berubah pikiran / tidak jadi beli</option>
                                </select>
                            </div>
                            <div class="modal-footer py-2">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Tidak Jadi</button>
                                <button type="submit" class="btn btn-sm btn-danger fw-bold">Ya, Batalkan Pesanan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @elseif(in_array($order->status, ['confirmed', 'processing', 'ready_for_pickup', 'shipping']))
            <div class="alert alert-light border py-2 px-3 small text-secondary rounded-3 d-flex align-items-center justify-content-between mb-0">
                <div>
                    <i class="icofont-info-circle text-primary me-1"></i>
                    <span>Pesanan sedang diproses/diantar toko.</span>
                </div>
                @if($order->merchant?->phone)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $order->merchant->phone) }}?text=Halo%20{{ urlencode($order->merchant->store_name) }},%20saya%20ingin%20mengajukan%20pembatalan%20pesanan%20nomor%20{{ $order->order_number }}" 
                       target="_blank" 
                       class="btn btn-sm btn-outline-success py-1 px-2 text-nowrap" style="font-size: 0.72rem;">
                        <i class="icofont-brand-whatsapp"></i> Chat Toko
                    </a>
                @endif
            </div>
        @endif

        <form action="{{ route('user.orders.reorder', $order->order_number) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success btn-lg w-100 rounded-3 fw-bold py-2 shadow-sm" style="font-size: 0.95rem;">
                <i class="icofont-refresh me-1"></i> Beli Lagi Produk Ini
            </button>
        </form>

        <a href="{{ route('user.orders.index') }}" class="btn btn-light border rounded-3 py-2 text-secondary fw-semibold small">
            Kembali ke Riwayat Pesanan
        </a>
    </div>
</div>
@endsection

@section('scripts')
    <!-- Leaflet.js JavaScript (Gratis OpenStreetMap) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const mapContainer = document.getElementById('liveTrackingMap');
        if (!mapContainer) return;

        // Koordinat Lokasi Dasar
        const originLat = {{ (float) ($order->merchant->latitude ?? -8.098254) }};
        const originLon = {{ (float) ($order->merchant->longitude ?? 112.168153) }};
        const destLat = {{ (float) ($order->shipping_latitude ?? $order->customer->latitude ?? -8.109300) }};
        const destLon = {{ (float) ($order->shipping_longitude ?? $order->customer->longitude ?? 112.189400) }};
        let driverLat = {{ (float) ($order->delivery?->current_latitude ?? $order->delivery?->driver?->latitude ?? -8.098254) }};
        let driverLon = {{ (float) ($order->delivery?->current_longitude ?? $order->delivery?->driver?->longitude ?? 112.168153) }};
        const isSelfPickup = {{ $isSelfPickup ? 'true' : 'false' }};
        let deliveryStatus = '{{ strtolower($order->delivery?->status ?? "pending") }}';

        // Inisialisasi Map Leaflet
        const initialCenter = (!isSelfPickup && (driverLat !== originLat)) ? [driverLat, driverLon] : [originLat, originLon];
        const map = L.map('liveTrackingMap', {
            zoomControl: true,
            scrollWheelZoom: false,
        }).setView(initialCenter, 14);

        // Tile Layer OpenStreetMap Gratis
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        // Icon Toko Koperasi
        const storeIcon = L.divIcon({
            className: 'custom-map-icon',
            html: '<div class="custom-map-pin pin-store" title="Toko Koperasi Blitar"><i class="icofont-shop"></i></div>',
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -18]
        });

        // Icon Rumah Pelanggan
        const destIcon = L.divIcon({
            className: 'custom-map-icon',
            html: '<div class="custom-map-pin pin-dest" title="Alamat Pengantaran"><i class="icofont-home"></i></div>',
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -18]
        });

        // Icon Motor KurirQ dengan container rotasi hadap jalan
        const driverIcon = L.divIcon({
            className: 'custom-map-icon custom-driver-marker',
            html: '<div class="custom-map-pin pin-driver" title="KurirQ Mak Ethek"><div class="driver-icon-inner" id="driverIconRotate"><span style="font-size: 20px; line-height: 1;">🛵</span></div></div>',
            iconSize: [42, 42],
            iconAnchor: [21, 21],
            popupAnchor: [0, -21]
        });

        // Pasang Marker Toko
        const storeMarker = L.marker([originLat, originLon], { icon: storeIcon }).addTo(map);
        storeMarker.bindPopup(`
            <div style="min-width: 160px;">
                <strong class="text-success d-block"><i class="icofont-shop me-1"></i>{{ addslashes($order->merchant->store_name ?? 'Gudang Koperasi Blitar') }}</strong>
                <small class="text-muted d-block">{{ addslashes($order->merchant->address ?? 'Jl. Merdeka No. 45, Blitar') }}</small>
            </div>
        `);

        let destMarker = null;
        let driverMarker = null;
        let routeBgPolyline = null;
        let routeMainPolyline = null;
        let routePickupPolyline = null;
        let currentRoadCoords = [];
        let previousDriverPos = [driverLat, driverLon];

        // Algoritma Snap-to-Road (Map Matching) untuk mengunci posisi GPS ke aspal jalan
        function projectPointToSegment(px, py, x1, y1, x2, y2) {
            const dx = x2 - x1;
            const dy = y2 - y1;
            if (dx === 0 && dy === 0) return { lat: x1, lon: y1 };
            let t = ((px - x1) * dx + (py - y1) * dy) / (dx * dx + dy * dy);
            t = Math.max(0, Math.min(1, t));
            return { lat: x1 + t * dx, lon: y1 + t * dy };
        }

        function snapToRoad(pointLat, pointLon, roadCoords, maxToleranceMeters = 85) {
            if (!roadCoords || roadCoords.length < 2) return [pointLat, pointLon];
            let bestPoint = [pointLat, pointLon];
            let minDistanceMeters = Infinity;

            for (let i = 0; i < roadCoords.length - 1; i++) {
                const p1 = roadCoords[i];
                const p2 = roadCoords[i + 1];
                const proj = projectPointToSegment(pointLat, pointLon, p1[0], p1[1], p2[0], p2[1]);

                const dLatM = (proj.lat - pointLat) * 111320;
                const dLonM = (proj.lon - pointLon) * (111320 * Math.cos(pointLat * Math.PI / 180));
                const distM = Math.sqrt(dLatM * dLatM + dLonM * dLonM);

                if (distM < minDistanceMeters) {
                    minDistanceMeters = distM;
                    bestPoint = [proj.lat, proj.lon];
                }
            }

            return (minDistanceMeters <= maxToleranceMeters) ? bestPoint : [pointLat, pointLon];
        }

        // Hitung sudut hadap motor (bearing)
        function calculateHeading(lat1, lon1, lat2, lon2) {
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const y = Math.sin(dLon) * Math.cos(lat2 * Math.PI / 180);
            const x = Math.cos(lat1 * Math.PI / 180) * Math.sin(lat2 * Math.PI / 180) -
                      Math.sin(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.cos(dLon);
            let brng = Math.atan2(y, x) * 180 / Math.PI;
            return (brng + 360) % 360;
        }

        // Ambil rute jalan riil dari OSRM (OpenStreetMap Routing Engine)
        async function fetchOsrmRoadRoute(startLat, startLon, endLat, endLon) {
            const url = `https://router.project-osrm.org/route/v1/driving/${startLon},${startLat};${endLon},${endLat}?overview=full&geometries=geojson`;
            try {
                const res = await fetch(url);
                if (!res.ok) throw new Error('OSRM response status ' + res.status);
                const data = await res.json();
                if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                    const r = data.routes[0];
                    const leafletCoords = r.geometry.coordinates.map(c => [c[1], c[0]]);
                    return {
                        coordinates: leafletCoords,
                        distanceKm: (r.distance / 1000).toFixed(1),
                        durationMin: Math.max(2, Math.ceil(r.duration / 60))
                    };
                }
            } catch (err) {
                console.warn('OSRM Road Routing notice (fallback aktif):', err.message);
            }
            return {
                coordinates: [[startLat, startLon], [endLat, endLon]],
                distanceKm: null,
                durationMin: null
            };
        }

        // Render rute jalan berlekuk (Dual Layer Polyline seperti Grab/Gojek)
        async function renderRoadRoute(startLat, startLon, endLat, endLon) {
            const routeData = await fetchOsrmRoadRoute(startLat, startLon, endLat, endLon);
            currentRoadCoords = routeData.coordinates;

            if (routeBgPolyline) map.removeLayer(routeBgPolyline);
            if (routeMainPolyline) map.removeLayer(routeMainPolyline);

            // Layer Bawah (Casing/Bayangan Jalan)
            routeBgPolyline = L.polyline(currentRoadCoords, {
                color: '#047857',
                weight: 7,
                opacity: 0.35,
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(map);

            // Layer Atas (Garis Jalan Hijau Neon Ojol)
            routeMainPolyline = L.polyline(currentRoadCoords, {
                color: '#10b981',
                weight: 4.5,
                opacity: 0.95,
                lineCap: 'round',
                lineJoin: 'round'
            }).addTo(map);

            // Perbarui badge jarak & waktu dengan data jalan riil jika tersedia
            if (routeData.distanceKm) {
                const distBadgeEl = document.getElementById('trackingDistanceBadge');
                if (distBadgeEl) distBadgeEl.textContent = routeData.distanceKm + ' km';
            }
            if (routeData.durationMin) {
                const timeEstEl = document.getElementById('trackingTimeEst');
                if (timeEstEl) timeEstEl.textContent = '± ' + routeData.durationMin + ' mnt';
            }

            return routeData;
        }

        if (!isSelfPickup) {
            // Pasang Marker Rumah Pelanggan
            destMarker = L.marker([destLat, destLon], { icon: destIcon }).addTo(map);
            destMarker.bindPopup(`
                <div style="min-width: 160px;">
                    <strong class="text-danger d-block"><i class="icofont-home me-1"></i>Alamat Pengantaran</strong>
                    <small class="text-dark d-block fw-semibold">{{ addslashes($order->recipient_name ?? $order->customer->name ?? 'Pelanggan') }}</small>
                    <small class="text-muted d-block">{{ addslashes($order->shipping_address ?? 'Kota Blitar') }}</small>
                </div>
            `);

            // Pasang Marker Kurir
            driverMarker = L.marker([driverLat, driverLon], { icon: driverIcon }).addTo(map);
            driverMarker.bindPopup(`
                <div style="min-width: 160px;">
                    <strong class="text-primary d-block">🛵 Kurir KurirQ Blitar</strong>
                    <small class="text-dark d-block fw-semibold" id="popupDriverName">{{ addslashes($order->delivery?->driver?->name ?? 'Kurir Mitra') }}</small>
                    <small class="text-muted d-block" id="popupDriverStatus">Melaju mengantar pesanan</small>
                </div>
            `);

            // Gambar rute jalan raya perdana mengikuti alur OpenStreetMap
            const routeStartLat = (['on_delivery', 'picked_up'].includes(deliveryStatus)) ? driverLat : originLat;
            const routeStartLon = (['on_delivery', 'picked_up'].includes(deliveryStatus)) ? driverLon : originLon;

            renderRoadRoute(routeStartLat, routeStartLon, destLat, destLon).then(() => {
                // Kunci posisi awal kurir ke jalan raya terdekat
                const initialSnapped = snapToRoad(driverLat, driverLon, currentRoadCoords);
                driverMarker.setLatLng(initialSnapped);
                previousDriverPos = initialSnapped;

                // Jika kurir masih menuju toko, buat garis dashed penghubung dari kurir ke toko
                if (['assigned', 'accepted'].includes(deliveryStatus)) {
                    routePickupPolyline = L.polyline([[driverLat, driverLon], [originLat, originLon]], {
                        color: '#0284c7',
                        weight: 3,
                        dashArray: '6, 6',
                        opacity: 0.8
                    }).addTo(map);
                }

                // Sesuaikan Zoom & View Peta agar seluruh titik muat dalam layar
                try {
                    const bounds = L.latLngBounds([
                        [originLat, originLon],
                        [driverLat, driverLon],
                        [destLat, destLon]
                    ]);
                    map.fitBounds(bounds, { padding: [45, 45], maxZoom: 16 });
                } catch (err) {
                    console.warn('Map fitBounds notice:', err);
                }
            });
        } else {
            storeMarker.openPopup();
        }

        // AJAX Polling Fungsi Live Tracking Real-Time
        const liveTrackingUrl = "{{ route('user.orders.liveTracking', $order->order_number) }}";
        let isPollingActive = true;
        let lastRouteUpdateCoord = null;

        async function updateTracking() {
            try {
                const response = await fetch(liveTrackingUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) return;
                const result = await response.json();
                if (!result.success) return;

                // Update posisi kurir jika ada
                if (result.driver && driverMarker) {
                    const rawLat = parseFloat(result.driver.latitude);
                    const rawLon = parseFloat(result.driver.longitude);

                    // Snap koordinat mentah GPS ke garis aspal jalan OSM
                    const snappedPos = snapToRoad(rawLat, rawLon, currentRoadCoords);
                    const newLat = snappedPos[0];
                    const newLon = snappedPos[1];

                    // Hitung heading / rotasi sudut motor
                    if (previousDriverPos && (previousDriverPos[0] !== newLat || previousDriverPos[1] !== newLon)) {
                        const heading = calculateHeading(previousDriverPos[0], previousDriverPos[1], newLat, newLon);
                        const rotateEl = document.getElementById('driverIconRotate');
                        if (rotateEl) {
                            rotateEl.style.transform = `rotate(${Math.round(heading)}deg)`;
                        }
                    }
                    previousDriverPos = [newLat, newLon];

                    // Gerakkan marker kurir dengan mulus (smooth movement)
                    driverMarker.setLatLng([newLat, newLon]);

                    // Update rute jalan jika driver bergerak signifikan (> 100m) atau berganti status
                    const currentStatus = result.delivery_status;
                    const shouldRecalculateRoute = !lastRouteUpdateCoord || 
                        (Math.abs(lastRouteUpdateCoord[0] - newLat) > 0.001 || Math.abs(lastRouteUpdateCoord[1] - newLon) > 0.001);

                    if (['on_delivery', 'picked_up'].includes(currentStatus) && shouldRecalculateRoute) {
                        lastRouteUpdateCoord = [newLat, newLon];
                        if (routePickupPolyline) {
                            map.removeLayer(routePickupPolyline);
                            routePickupPolyline = null;
                        }
                        renderRoadRoute(newLat, newLon, result.destination.latitude, result.destination.longitude);
                    }

                    // Update UI Cards
                    const driverNameEl = document.getElementById('trackingDriverName');
                    if (driverNameEl) driverNameEl.textContent = result.driver.name;

                    const distBadgeEl = document.getElementById('trackingDistanceBadge');
                    if (distBadgeEl && !currentRoadCoords.length) {
                        distBadgeEl.textContent = result.distance_remaining_km + ' km';
                    }

                    const timeEstEl = document.getElementById('trackingTimeEst');
                    if (timeEstEl && !currentRoadCoords.length) {
                        timeEstEl.textContent = '± ' + result.estimated_minutes + ' mnt';
                    }

                    const popupDriverName = document.getElementById('popupDriverName');
                    if (popupDriverName) popupDriverName.textContent = result.driver.name;

                    const statusTextEl = document.getElementById('trackingStatusText');
                    if (statusTextEl) {
                        if (result.delivery_status === 'delivered') {
                            statusTextEl.textContent = 'Pesanan telah sampai di tujuan!';
                            statusTextEl.className = 'd-block text-success fw-bold';
                            isPollingActive = false;
                        } else if (result.delivery_status === 'on_delivery' || result.delivery_status === 'picked_up') {
                            statusTextEl.textContent = 'Sedang meluncur ke alamat Anda';
                        } else {
                            statusTextEl.textContent = 'Kurir bersiap menuju toko';
                        }
                    }

                    const lastUpdatedEl = document.getElementById('lastUpdatedTime');
                    if (lastUpdatedEl) {
                        const now = new Date();
                        const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        lastUpdatedEl.textContent = 'Update: ' + timeStr;
                    }
                }
            } catch (err) {
                console.error('Error fetching live tracking:', err);
            }
        }

        // Tombol Sync Manual
        const btnRefresh = document.getElementById('btnManualRefresh');
        const refreshLabel = document.getElementById('refreshLabel');
        const refreshIcon = document.getElementById('refreshIcon');

        if (btnRefresh) {
            btnRefresh.addEventListener('click', async function () {
                if (refreshIcon) refreshIcon.classList.add('icofont-spin');
                if (refreshLabel) refreshLabel.textContent = 'Sync...';
                await updateTracking();
                setTimeout(() => {
                    if (refreshIcon) refreshIcon.classList.remove('icofont-spin');
                    if (refreshLabel) refreshLabel.textContent = 'Sync';
                }, 500);
            });
        }

        // Jalankan polling otomatis tiap 8 detik jika status pengiriman sedang aktif
        const activeDeliveryStatuses = ['assigned', 'accepted', 'picked_up', 'on_delivery'];
        if (!isSelfPickup && activeDeliveryStatuses.includes(deliveryStatus)) {
            const intervalId = setInterval(() => {
                if (!isPollingActive) {
                    clearInterval(intervalId);
                    return;
                }
                updateTracking();
            }, 8000);
        }
    });
    </script>
@endsection
