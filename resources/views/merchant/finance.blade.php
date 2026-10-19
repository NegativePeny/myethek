@extends('layouts.merchant')

@section('title', 'Laporan Keuangan - ' . $merchant->store_name)

@section('header_extra')
    <!-- Switcher Akun Mitra Demo -->
    <div class="mt-2 pt-2 border-top border-white-50">
        <form action="{{ route('merchant.switch') }}" method="POST" class="d-flex align-items-center gap-2">
            @csrf
            <span style="font-size: 0.72rem; white-space: nowrap;">Peran Toko:</span>
            <select name="merchant_id" class="form-select form-select-sm bg-white text-dark py-0" style="font-size: 0.75rem;" onchange="this.form.submit()">
                @foreach($allMerchants as $m)
                    <option value="{{ $m->id }}" {{ $merchant->id == $m->id ? 'selected' : '' }}>
                        [{{ strtoupper($m->tier) }}] {{ $m->store_name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>
@endsection

@section('content')
<div class="p-3">
    <!-- Header Modul -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark">Laporan Keuangan Toko</h5>
            <small class="text-muted" style="font-size: 0.75rem;">Rekapitulasi omset, escrow, dan mutasi saldo dompet koperasi</small>
        </div>
        <span class="badge bg-success" style="font-size: 0.7rem;">
            {{ strtoupper($merchant->tier) }}
        </span>
    </div>

    <!-- Kartu Saldo Dompet Internal -->
    <div class="card border mb-3 shadow-sm" style="background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); color: white;">
        <div class="card-body p-3">
            <span class="opacity-75 small d-block mb-1" style="font-size: 0.75rem;">Saldo Siap Digunakan / Kulakan</span>
            <h3 class="fw-bold mb-3">Rp {{ number_format($wallet->balance_available, 0, ',', '.') }}</h3>

            <div class="d-flex justify-content-between pt-2 border-top border-white-50 small opacity-90" style="font-size: 0.75rem;">
                <span><i class="bi bi-clock-history me-1"></i> Saldo Tertahan (Escrow):</span>
                <strong>Rp {{ number_format($wallet->balance_pending, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <!-- Ringkasan Omset Penjualan -->
    <div class="card border mb-3 shadow-sm bg-white">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-3">
                <i class="bi bi-graph-up-arrow text-success me-1"></i> Ringkasan Penjualan Retail
            </h6>

            <div class="row g-2 text-center">
                <div class="col-4 border-end">
                    <small class="text-muted d-block" style="font-size: 0.68rem;">Hari Ini</small>
                    <span class="fw-bold text-dark" style="font-size: 0.85rem;">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="col-4 border-end">
                    <small class="text-muted d-block" style="font-size: 0.68rem;">Bulan Ini</small>
                    <span class="fw-bold text-primary" style="font-size: 0.85rem;">Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block" style="font-size: 0.68rem;">Total Omset</small>
                    <span class="fw-bold text-success" style="font-size: 0.85rem;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Rekening Bersama (Escrow Koperasi) -->
    <div class="card border mb-3 shadow-sm bg-white">
        <div class="card-body p-3">
            <h6 class="fw-bold small text-dark mb-2">
                <i class="bi bi-shield-check text-primary me-1"></i> Status Escrow Rekening Bersama
            </h6>
            <p class="text-muted small mb-3" style="font-size: 0.72rem;">
                Dana pembayaran belanja warga diamankan di holding koperasi hingga pesanan diverifikasi selesai via OTP.
            </p>

            <div class="d-flex justify-content-between align-items-center py-2 border-bottom small">
                <span class="text-secondary">Dana Masih Tertahan di Escrow:</span>
                <span class="fw-bold text-warning-emphasis">Rp {{ number_format($pendingEscrow, 0, ',', '.') }}</span>
            </div>

            <div class="d-flex justify-content-between align-items-center py-2 small">
                <span class="text-secondary">Total Dana Sukses Cair ke Dompet:</span>
                <span class="fw-bold text-success">Rp {{ number_format($releasedEscrow, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Riwayat Mutasi Dompet Koperasi -->
    <div class="card border mb-3 shadow-sm bg-white">
        <div class="card-header bg-light d-flex align-items-center justify-content-between p-2 px-3">
            <span class="fw-bold small text-dark">
                <i class="bi bi-journal-text me-1"></i> Riwayat Mutasi Dompet
            </span>
            <small class="text-muted" style="font-size: 0.7rem;">20 Catatan Terakhir</small>
        </div>

        <div class="card-body p-0">
            @if($mutations->isEmpty())
                <div class="p-3 text-center text-muted small">
                    Belum ada riwayat mutasi keuangan pada dompet toko ini.
                </div>
            @else
                <div class="list-group list-group-flush small">
                    @foreach($mutations as $mut)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <span class="fw-semibold text-dark d-block">{{ $mut->description }}</span>
                                    <small class="text-muted" style="font-size: 0.68rem;">
                                        {{ $mut->created_at->translatedFormat('d M Y, H:i') }}
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold {{ $mut->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                        {{ $mut->type === 'credit' ? '+' : '-' }} Rp {{ number_format($mut->amount, 0, ',', '.') }}
                                    </span>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">
                                        Sisa: Rp {{ number_format($mut->balance_after, 0, ',', '.') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
