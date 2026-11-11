@extends('layouts.storefront')

@section('title', 'Status Pesanan ' . $order->order_number . ' - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center justify-content-between">
        <a href="{{ route('storefront.index') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
            <i class="icofont-rounded-left"></i> Beranda
        </a>
        <div class="fw-bold small text-dark">
            Pesanan #{{ $order->order_number }}
        </div>
    </div>
@endsection

@section('content')
<div class="osahan-status pb-4">
    <!-- Success Badge Top Bar -->
    <div class="p-3 text-center bg-white border-bottom">
        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow mb-2" style="width: 58px; height: 58px;">
            <i class="icofont-check fs-2"></i>
        </div>
        <h5 class="fw-bold text-dark mb-1">Pesanan Berhasil Diterima!</h5>
        <p class="text-muted small mb-0">Pesanan telah masuk ke antrean sistem Mak Ethek ({{ strtoupper($order->order_type ?? 'B2C') }}).</p>
    </div>

    <!-- DOKU Payment Gateway Info & Sandbox Simulator (CUS-006) -->
    @if($order->payment)
        <div class="p-3 mx-3 mt-3 rounded-3 border bg-white shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold small text-dark">
                    <i class="icofont-credit-card text-primary me-1"></i> Tagihan DOKU ({{ strtoupper($order->payment->channel) }})
                </span>
                @if($order->payment->status === 'paid')
                    <span class="badge bg-success text-white">LUNAS TERVERIFIKASI</span>
                @elseif($order->payment->status === 'pending')
                    <span class="badge bg-warning text-dark">MENUNGGU PEMBAYARAN</span>
                @else
                    <span class="badge bg-danger text-white">{{ strtoupper($order->payment->status) }}</span>
                @endif
            </div>

            <div class="p-2 rounded bg-light border small mb-2">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">No. Referensi DOKU:</span>
                    <strong class="font-monospace text-dark">{{ $order->payment->payment_reference }}</strong>
                </div>
                @if(!empty($order->payment->raw_payload['virtual_account_or_qr']))
                    <div class="d-flex justify-content-between mt-1">
                        <span class="text-muted">Kode VA / QRIS:</span>
                        <strong class="font-monospace text-primary text-truncate" style="max-width: 190px;">{{ $order->payment->raw_payload['virtual_account_or_qr'] }}</strong>
                    </div>
                @endif
                @if($order->payment->expired_at && $order->payment->status === 'pending')
                    <div class="d-flex justify-content-between mt-1">
                        <span class="text-muted">Batas Waktu Bayar:</span>
                        <span class="text-danger fw-semibold">{{ $order->payment->expired_at->format('d/m/Y H:i') }} WIB</span>
                    </div>
                @endif
            </div>

            @if($order->payment->status === 'pending')
                <div class="d-flex gap-2 mt-2">
                    <form action="{{ route('storefront.simulateDoku', $order->order_number) }}" method="POST" class="flex-grow-1">
                        @csrf
                        <input type="hidden" name="status" value="SUCCESS">
                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold py-2">
                            <i class="icofont-check-circled me-1"></i> Simulasi Bayar Lunas (DOKU)
                        </button>
                    </form>
                    <form action="{{ route('storefront.simulateDoku', $order->order_number) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="EXPIRED">
                        <button type="submit" class="btn btn-sm btn-outline-danger py-2">
                            Simulasi Expired
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @endif

    <!-- Order Milestone Status Stepper (Grofar Pattern) -->
    <div class="p-3 bg-white border-bottom mt-2">
        <h6 class="fw-bold mb-3"><i class="icofont-clock-time text-success me-1"></i> Status Pengiriman</h6>
        
        <div class="tracking-wrap">
            @php
                $status = strtolower($order->status ?? 'pending');
                $packingStatus = strtolower($order->packing_status ?? 'not_started');
                $deliveryStatus = strtolower($order->delivery->status ?? 'pending');
                $isSelfPickup = ($order->delivery?->delivery_type === 'self_pickup');

                $step = 1;
                if (in_array($status, ['delivered', 'completed']) || in_array($deliveryStatus, ['delivered'])) {
                    $step = 4;
                } elseif (
                    in_array($status, ['on_delivery', 'shipping', 'shipped', 'picked_up']) ||
                    in_array($deliveryStatus, ['on_delivery', 'picked_up']) ||
                    ($packingStatus === 'handed_over' && in_array($deliveryStatus, ['assigned', 'accepted'])) ||
                    in_array($status, ['assigned']) ||
                    ($isSelfPickup && ($status === 'ready_for_pickup' || $packingStatus === 'packed' || $packingStatus === 'handed_over'))
                ) {
                    $step = 3;
                } elseif (
                    in_array($status, ['processing', 'picking', 'packed', 'ready_for_pickup', 'confirmed', 'accepted']) ||
                    in_array($packingStatus, ['picking', 'packed']) ||
                    ($status === 'paid')
                ) {
                    $step = 2;
                }
            @endphp

            <div class="my-2 step active">
                <span class="icon text-success"><i class="icofont-check-circled fs-5"></i></span>
                <span class="text small fw-semibold ms-2">1. Pesanan Diterima Sistem</span>
            </div>

            <div class="my-2 step {{ $step >= 2 ? 'active' : '' }}">
                <span class="icon {{ $step >= 2 ? 'text-success' : 'text-muted' }}">
                    <i class="icofont-{{ $step >= 2 ? 'check-circled' : 'clock-time' }} fs-5"></i>
                </span>
                <span class="text small fw-semibold ms-2 {{ $step >= 2 ? 'text-dark' : 'text-muted' }}">
                    2. Disiapkan Gudang / Toko Mitra ({{ strtoupper($order->status) }})
                </span>
            </div>

            <div class="my-2 step {{ $step >= 3 ? 'active' : '' }}">
                <span class="icon {{ $step >= 3 ? 'text-success' : 'text-muted' }}">
                    <i class="icofont-{{ $step >= 3 ? 'motor-bike' : 'clock-time' }} fs-5"></i>
                </span>
                <span class="text small fw-semibold ms-2 {{ $step >= 3 ? 'text-dark' : 'text-muted' }}">
                    3. Dalam Perjalanan Kurir ({{ strtoupper(str_replace('_', ' ', $order->delivery->delivery_type)) }})
                </span>
            </div>

            <div class="my-2 step {{ $step >= 4 ? 'active' : '' }}">
                <span class="icon {{ $step >= 4 ? 'text-success' : 'text-muted' }}">
                    <i class="icofont-{{ $step >= 4 ? 'check-circled' : 'clock-time' }} fs-5"></i>
                </span>
                <span class="text small fw-semibold ms-2 {{ $step >= 4 ? 'text-dark' : 'text-muted' }}">
                    4. Pesanan Selesai Terkirim
                </span>
            </div>
        </div>

        <!-- Kode OTP Serah Terima -->
        <div class="p-3 rounded-3 bg-light border text-center mt-3">
            <div class="text-muted small fw-bold" style="font-size: 0.7rem;">
                KODE OTP SERAH-TERIMA PENGANTARAN:
            </div>
            <div class="fw-bold fs-3 text-success font-monospace my-1" style="letter-spacing: 4px;">
                {{ $order->delivery->completion_otp }}
            </div>
            <small class="text-secondary d-block" style="font-size: 0.68rem;">
                Tunjukkan 4 angka ini kepada kurir saat barang tiba di alamat penerima.
            </small>
        </div>
    </div>

    <!-- WhatsApp Confirmation Link -->
    <div class="p-3 mx-3 mt-3 rounded-3 border bg-white shadow-sm" style="border-left: 4px solid #25d366 !important;">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="icofont-brand-whatsapp text-success fs-3"></i>
            <div>
                <h6 class="mb-0 fw-bold text-dark small">Konfirmasi ke Toko / Gudang Mitra</h6>
                <small class="text-muted" style="font-size: 0.72rem;">Hubungi langsung bila butuh respon cepat.</small>
            </div>
        </div>
        <div class="d-grid">
            <a href="{{ $waLink }}" target="_blank" class="btn btn-success fw-bold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size: 0.85rem;">
                <i class="icofont-brand-whatsapp fs-5"></i> Kirim Rincian via WhatsApp
            </a>
        </div>
    </div>

    <!-- Info Alamat & Toko Pengirim -->
    <div class="p-3 bg-white border-bottom mt-3">
        <h6 class="fw-bold mb-2 small"><i class="icofont-location-pin text-danger me-1"></i> Penerima & Alamat Pengantaran</h6>
        <p class="m-0 small text-dark fw-semibold">
            {{ $order->recipient_name ?? $order->customer->name }} ({{ $order->recipient_phone ?? $order->customer->phone }})
        </p>
        <p class="m-0 small text-muted">{{ $order->shipping_address }}</p>
    </div>

    <div class="p-3 bg-white border-bottom">
        <h6 class="fw-bold mb-2 small"><i class="icofont-shop text-success me-1"></i> Toko Mitra Pelayan</h6>
        <p class="m-0 small text-dark fw-semibold">{{ $order->merchant->store_name }}</p>
        <p class="m-0 small text-muted">{{ $order->merchant->address }} ({{ $order->merchant->territory?->name }})</p>
    </div>

    <!-- Rincian Total Belanja -->
    <div class="p-3 bg-white border-bottom">
        <h6 class="fw-bold mb-2 small"><i class="icofont-bill-alt text-primary me-1"></i> Rincian Pembayaran</h6>
        
        <div class="d-flex flex-column gap-2 mb-2 pb-2 border-bottom">
            @foreach($order->items as $item)
                @php
                    $prodImage = $item->product?->image;
                    $pIndex = ($item->product_id % 12) + 1;
                    $fallbackImg = asset("grofar/img/listing/v{$pIndex}.jpg");
                    $dispImg = $prodImage ? asset($prodImage) : $fallbackImg;
                @endphp
                <div class="d-flex align-items-center justify-content-between small">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ $dispImg }}" onerror="this.onerror=null; this.src='{{ $fallbackImg }}';" class="rounded border bg-white" style="width: 36px; height: 36px; object-fit: contain;" alt="{{ $item->product_name }}">
                        <div>
                            <span class="text-truncate fw-semibold text-dark d-block" style="max-width: 180px;">{{ $item->product_name }}</span>
                            <small class="text-muted">{{ $item->quantity }} {{ $item->unit_name }} &times; Rp {{ number_format($item->price, 0, ',', '.') }}</small>
                        </div>
                    </div>
                    <strong class="text-dark">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</strong>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Subtotal:</span>
            <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
        </div>
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Ongkos Kirim ({{ strtoupper(str_replace('_', ' ', $order->delivery->delivery_type)) }}):</span>
            <span>Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span>
        </div>
        <div class="d-flex justify-content-between small text-muted mb-2">
            <span>Biaya Platform Koperasi:</span>
            <span>Rp {{ number_format($order->platform_fee, 0, ',', '.') }}</span>
        </div>
        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
            <h6 class="fw-bold mb-0 text-dark">Total Biaya:</h6>
            <h6 class="fw-bold mb-0 text-success fs-5">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</h6>
        </div>
    </div>

    <!-- Tombol Kembali ke Beranda -->
    <div class="p-3 mt-3">
        <a href="{{ route('storefront.index') }}" class="btn btn-outline-success w-100 py-2 rounded-3 fw-bold">
            <i class="icofont-shopping-cart me-1"></i> Belanja Lagi
        </a>
    </div>
</div>
@endsection
