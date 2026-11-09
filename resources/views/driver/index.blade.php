@extends('layouts.storefront')

@section('title', 'Portal Driver KurirQ - Mak Ethek')

@section('header_extra')
    <div class="px-3 py-2 bg-dark text-white d-flex align-items-center justify-content-between rounded-3 mt-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bicycle text-success fs-4"></i>
            <div>
                <div class="fw-bold small">Mitra Driver KurirQ Blitar</div>
                <small class="text-white-50" style="font-size: 0.68rem;">{{ $driver->name }} ({{ $driver->phone }})</small>
            </div>
        </div>
        <span class="badge bg-success text-white small font-monospace">ONLINE</span>
    </div>
@endsection

@section('content')
<div class="p-3">
    <!-- Kartu Saldo Dompet Driver -->
    <div class="card border mb-3 shadow-sm bg-light">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold small text-dark d-flex align-items-center gap-1">
                    <i class="bi bi-cash-coin text-success"></i> Saldo Pendapatan Ongkir KurirQ
                </span>
                <span class="badge bg-white text-secondary border small" style="font-size: 0.65rem;">
                    100% Hak Kurir
                </span>
            </div>
            
            <div class="d-flex align-items-baseline gap-2 mb-2">
                <h4 class="fw-bold text-success mb-0">
                    Rp {{ number_format($wallet->balance_available, 0, ',', '.') }}
                </h4>
                <small class="text-muted">Siap Ditarik</small>
            </div>

            <button type="button" class="btn btn-sm btn-outline-success w-100 py-1" style="font-size: 0.72rem;"
                    onclick="alert('Pencairan ongkir KurirQ diproses ke rekening/e-wallet Anda melalui Settlement Koperasi.');">
                <i class="bi bi-wallet2 me-1"></i> Tarik Saldo ke Rekening / E-Wallet
            </button>
        </div>
    </div>

    <!-- Pengantaran Aktif & Penugasan Masuk (DRV-001 s/d DRV-006) -->
    <div class="mb-3">
        <h6 class="fw-bold text-dark mb-2 small d-flex align-items-center gap-1">
            <i class="bi bi-box2-heart-fill text-primary"></i> Tugas Pengantaran Saya ({{ $activeDeliveries->count() }})
        </h6>

        @if($activeDeliveries->isEmpty())
            <div class="p-3 rounded border bg-white text-center text-muted small">
                Tidak ada tugas pengantaran aktif saat ini.
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach($activeDeliveries as $del)
                    @php
                        $ord = $del->order;
                        $recipientName = $ord->recipient_name ?? $ord->customer?->name;
                        $recipientPhone = $ord->recipient_phone ?? $ord->customer?->phone;
                        $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone ?? '');
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }
                        $mapsQuery = urlencode($ord->shipping_address ?? 'Kota Blitar');
                    @endphp
                    <div class="card border border-primary shadow-sm">
                        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold font-monospace small">{{ $ord->order_number }}</span>
                                <span class="badge bg-dark ms-1" style="font-size: 0.62rem;">{{ strtoupper($del->status) }}</span>
                            </div>
                            <span class="badge bg-white text-primary fw-bold small">Ongkir: Rp {{ number_format($del->fee, 0, ',', '.') }}</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">1. Titik Pickup Gudang/Toko:</small>
                                <strong class="small text-dark">{{ $ord->merchant->store_name }}</strong>
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $ord->merchant->address }}</div>
                            </div>

                            <div class="mb-3 pt-2 border-top">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">2. Titik Antar Pelanggan:</small>
                                <strong class="small text-dark">{{ $recipientName }}</strong> ({{ $recipientPhone }})
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $ord->shipping_address }}</div>
                            </div>

                            <!-- Navigasi Google Maps & WhatsApp (DRV-003) -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $mapsQuery }}" target="_blank"
                                       class="btn btn-sm btn-outline-primary w-100 fw-semibold py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-geo-alt-fill me-1"></i> Navigasi Maps
                                    </a>
                                </div>
                                <div class="col-6">
                                    @if($cleanPhone)
                                        <a href="https://wa.me/{{ $cleanPhone }}?text=Halo%20{{ urlencode($recipientName) }},%20saya%20driver%20KurirQ%20Mak%20Ethek%20mengantar%20pesanan%20{{ $ord->order_number }}." 
                                           target="_blank" class="btn btn-sm btn-outline-success w-100 fw-semibold py-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-whatsapp me-1"></i> WA Penerima
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if($del->status === 'assigned')
                                <!-- Respon Terima / Tolak Tugas dengan Alasan (DRV-002) -->
                                <div class="p-2 rounded bg-warning-subtle border border-warning mb-2">
                                    <div class="fw-bold small text-dark mb-2">Tugas Baru Ditugaskan Admin — Konfirmasi:</div>
                                    <div class="d-flex gap-2">
                                        <form action="{{ route('driver.respond', $del->id) }}" method="POST" class="flex-grow-1">
                                            @csrf
                                            <input type="hidden" name="decision" value="accept">
                                            <button type="submit" class="btn btn-sm btn-success w-100 fw-bold">
                                                <i class="bi bi-check-circle me-1"></i> Terima Tugas
                                            </button>
                                        </form>
                                        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#rejectTask{{ $del->id }}">
                                            Tolak
                                        </button>
                                    </div>
                                    <div class="collapse mt-2" id="rejectTask{{ $del->id }}">
                                        <form action="{{ route('driver.respond', $del->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="decision" value="reject">
                                            <input type="text" name="rejection_reason" class="form-control form-control-sm mb-1" placeholder="Alasan menolak (contoh: Ban bocor)..." required>
                                            <button type="submit" class="btn btn-sm btn-danger w-100">Kirim Penolakan</button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <!-- Form Verifikasi OTP Serah-Terima & Upload Bukti Foto POD (DRV-005 & DRV-006) -->
                                <form action="{{ route('driver.complete', $del->id) }}" method="POST" enctype="multipart/form-data" class="p-2 bg-light rounded border">
                                    @csrf
                                    <label class="form-label small fw-bold text-dark mb-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-key-fill text-warning me-1"></i> Masukkan Kode OTP Penerima:
                                    </label>
                                    <input type="text" name="otp" class="form-control form-control-sm text-center font-monospace fw-bold mb-2" 
                                           placeholder="Contoh: {{ $del->completion_otp }}" required maxlength="6">

                                    <label class="form-label small fw-semibold text-secondary mb-1" style="font-size: 0.7rem;">
                                        <i class="bi bi-camera me-1"></i> Foto Bukti Serah Terima / POD (Opsional):
                                    </label>
                                    <input type="file" name="proof_photo" class="form-control form-control-sm mb-2" accept="image/*">

                                    <button type="submit" class="btn btn-sm btn-success w-100 fw-bold py-2">
                                        <i class="bi bi-check2-circle me-1"></i> Verifikasi OTP & Selesaikan Pengiriman
                                    </button>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.65rem;">
                                        * Ongkir Rp {{ number_format($del->fee, 0, ',', '.') }} langsung cair ke dompet saat OTP valid.
                                    </small>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Pengantaran Tersedia (Siap Diambil di Gudang/Toko) -->
    <div class="mb-4">
        <h6 class="fw-bold text-dark mb-2 small d-flex align-items-center gap-1">
            <i class="bi bi-broadcast text-success"></i> Pesanan Siap Pickup di Gudang ({{ $availableDeliveries->count() }})
        </h6>

        @if($availableDeliveries->isEmpty())
            <div class="p-3 rounded border bg-white text-center text-muted small">
                Belum ada pesanan baru di antrean bursa terbuka.
            </div>
        @else
            <div class="d-flex flex-column gap-2">
                @foreach($availableDeliveries as $avail)
                    @php $o = $avail->order; @endphp
                    <div class="card border shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="fw-bold font-monospace small text-dark">{{ $o->order_number }}</span>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">Lokasi Pickup: {{ $o->merchant->store_name }}</small>
                                </div>
                                <span class="badge bg-success text-white small">
                                    Rp {{ number_format($avail->fee, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between text-secondary small mb-2" style="font-size: 0.72rem;">
                                <span><i class="bi bi-geo-alt text-danger me-1"></i> Jarak: {{ $avail->distance_km }} km</span>
                                <span>Penerima: {{ $o->recipient_name ?? $o->customer->name }}</span>
                            </div>

                            <form action="{{ route('driver.pickup', $avail->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success w-100 fw-bold py-1">
                                    <i class="bi bi-hand-index-thumb me-1"></i> Ambil & Pickup Pesanan Ini
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
