@extends('layouts.storefront')

@section('title', 'Portal Kurir Lokal - Mak Ethek')

@section('header_extra')
    <div class="px-3 py-2 bg-dark text-white d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bicycle text-success fs-4"></i>
            <div>
                <div class="fw-bold small">Mitra Kurir Lokal Desa</div>
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
                    <i class="bi bi-cash-coin text-success"></i> Saldo Pendapatan Ongkir
                </span>
                <span class="badge bg-white text-secondary border small" style="font-size: 0.65rem;">
                    Bebas Potongan Bank
                </span>
            </div>
            
            <div class="d-flex align-items-baseline gap-2 mb-2">
                <h4 class="fw-bold text-success mb-0">
                    Rp {{ number_format($wallet->balance_available, 0, ',', '.') }}
                </h4>
                <small class="text-muted">Siap Ditarik</small>
            </div>

            <button type="button" class="btn btn-sm btn-outline-success w-100 py-1" style="font-size: 0.72rem;"
                    onclick="alert('Pencairan ongkir kurir otomatis ke rekening bank/e-wallet Anda melalui batch disbursement koperasi.');">
                <i class="bi bi-wallet2 me-1"></i> Tarik Saldo ke Rekening / E-Wallet
            </button>
        </div>
    </div>

    <!-- Pengantaran Aktif (Sedang Dibawa Driver Ini) -->
    <div class="mb-3">
        <h6 class="fw-bold text-dark mb-2 small d-flex align-items-center gap-1">
            <i class="bi bi-box2-heart-fill text-primary"></i> Tugas Pengantaran Aktif ({{ $activeDeliveries->count() }})
        </h6>

        @if($activeDeliveries->isEmpty())
            <div class="p-3 rounded border bg-white text-center text-muted small">
                Tidak ada pengantaran yang sedang berjalan saat ini.
            </div>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach($activeDeliveries as $del)
                    @php
                        $ord = $del->order;
                        $cleanPhone = preg_replace('/[^0-9]/', '', $ord->customer->phone ?? '');
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }
                    @endphp
                    <div class="card border border-primary shadow-sm">
                        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold font-monospace small">{{ $ord->order_number }}</span>
                            <span class="badge bg-white text-primary fw-bold small">Ongkir: Rp {{ number_format($del->fee, 0, ',', '.') }}</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Ambil di Toko:</small>
                                <strong class="small text-dark">{{ $ord->merchant->store_name }}</strong>
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $ord->merchant->address }}</div>
                            </div>

                            <div class="mb-3 pt-2 border-top">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Antar ke Pelanggan:</small>
                                <strong class="small text-dark">{{ $ord->customer->name }}</strong> ({{ $ord->customer->phone }})
                                <div class="text-muted" style="font-size: 0.72rem;">{{ $ord->shipping_address }}</div>
                            </div>

                            <!-- Tombol WhatsApp Pelanggan -->
                            @if($cleanPhone)
                                <div class="d-grid mb-2">
                                    <a href="https://wa.me/{{ $cleanPhone }}?text=Halo%20{{ $ord->customer->name }},%20saya%20kurir%20Mak%20Ethek%20sedang%20dalam%20perjalanan%20mengantar%20pesanan%20Anda." 
                                       target="_blank" class="btn btn-sm btn-outline-success fw-semibold py-1">
                                        <i class="bi bi-whatsapp me-1"></i> Hubungi WhatsApp Pelanggan
                                    </a>
                                </div>
                            @endif

                            <!-- Form Verifikasi OTP Serah-Terima -->
                            <form action="{{ route('driver.complete', $del->id) }}" method="POST" class="p-2 bg-light rounded border">
                                @csrf
                                <label class="form-label small fw-bold text-dark mb-1" style="font-size: 0.72rem;">
                                    <i class="bi bi-key-fill text-warning me-1"></i> Minta 4-Digit OTP ke Pelanggan:
                                </label>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" name="otp" class="form-control text-center font-monospace fw-bold" 
                                           placeholder="Contoh: {{ $del->completion_otp }}" required maxlength="6">
                                    <button type="submit" class="btn btn-success fw-bold">
                                        <i class="bi bi-check2-circle me-1"></i> Selesaikan
                                    </button>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.68rem;">
                                    * Ongkos kirim Rp {{ number_format($del->fee, 0, ',', '.') }} akan langsung masuk ke dompet Anda saat OTP cocok.
                                </small>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Pengantaran Tersedia (Siap Diambil di Sekitar Desa) -->
    <div class="mb-4">
        <h6 class="fw-bold text-dark mb-2 small d-flex align-items-center gap-1">
            <i class="bi bi-broadcast text-success"></i> Pesanan Siap Diambil ({{ $availableDeliveries->count() }})
        </h6>

        @if($availableDeliveries->isEmpty())
            <div class="p-3 rounded border bg-white text-center text-muted small">
                Belum ada pesanan baru yang siap diambil di area Anda.
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
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">Toko: {{ $o->merchant->store_name }} ({{ $o->merchant->territory?->name }})</small>
                                </div>
                                <span class="badge bg-success text-white small">
                                    Rp {{ number_format($avail->fee, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between text-secondary small mb-2" style="font-size: 0.72rem;">
                                <span><i class="bi bi-geo-alt text-danger me-1"></i> Jarak Antar: {{ $avail->distance_km }} km</span>
                                <span>Tujuan: {{ $o->customer->name }}</span>
                            </div>

                            <form action="{{ route('driver.pickup', $avail->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success w-100 fw-bold py-1">
                                    <i class="bi bi-hand-index-thumb me-1"></i> Ambil Pesanan Ini
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
