@extends('layouts.merchant')

@section('title', 'Audit & Rekapitulasi Holding Koperasi Jatim')

@section('content')
<div class="p-3">
    <!-- Header Rekap Eksekutif Koperasi Jatim -->
    <div class="p-3 mb-3 rounded-3" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: white;">
        <span class="badge bg-success text-white fw-bold mb-1" style="font-size: 0.65rem;">
            PERSATUAN KOPERASI JAWA TIMUR
        </span>
        <h5 class="fw-bold mb-1">Financial Holding & Territory Ledger</h5>
        <p class="mb-0 text-white-50 small" style="font-size: 0.73rem;">
            Konsolidasi transaksi perdagangan berjenjang dari level provinsi sampai warung kelontong desa.
        </p>
    </div>

    <!-- 4 Kartu Metrik Keuangan Utama -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="p-3 rounded-3 border bg-white shadow-sm h-100">
                <small class="text-muted d-block" style="font-size: 0.68rem;">GMV RETAIL DESA:</small>
                <h6 class="fw-bold text-success mb-0 mt-1">Rp {{ number_format($retailGmv, 0, ',', '.') }}</h6>
                <small class="text-muted" style="font-size: 0.65rem;">Belanja Warga</small>
            </div>
        </div>

        <div class="col-6">
            <div class="p-3 rounded-3 border bg-white shadow-sm h-100">
                <small class="text-muted d-block" style="font-size: 0.68rem;">KULAKAN GROSIR B2B:</small>
                <h6 class="fw-bold text-primary mb-0 mt-1">Rp {{ number_format($procurementGmv, 0, ',', '.') }}</h6>
                <small class="text-muted" style="font-size: 0.65rem;">Pasokan Berjenjang</small>
            </div>
        </div>

        <div class="col-6">
            <div class="p-3 rounded-3 border bg-white shadow-sm h-100">
                <small class="text-muted d-block" style="font-size: 0.68rem;">ESCROW TERTAHAN:</small>
                <h6 class="fw-bold text-warning mb-0 mt-1">Rp {{ number_format($escrowHeld, 0, ',', '.') }}</h6>
                <small class="text-muted" style="font-size: 0.65rem;">Status Held (Belum Selesai)</small>
            </div>
        </div>

        <div class="col-6">
            <div class="p-3 rounded-3 border bg-white shadow-sm h-100">
                <small class="text-muted d-block" style="font-size: 0.68rem;">FEE PLATFORM KOPERASI:</small>
                <h6 class="fw-bold text-dark mb-0 mt-1">Rp {{ number_format($platformFeeTotal, 0, ',', '.') }}</h6>
                <small class="text-muted" style="font-size: 0.65rem;">Pendapatan Induk</small>
            </div>
        </div>
    </div>

    <!-- Matriks Kelembagaan Mitra Jaringan -->
    <div class="card border mb-3 shadow-sm">
        <div class="card-header bg-white py-2">
            <h6 class="fw-bold mb-0 small text-dark">
                <i class="bi bi-diagram-3 text-primary me-1"></i> Sebaran Ekosistem Mitra & Wilayah
            </h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-2 text-center" style="font-size: 0.75rem;">
                <div class="col-3 p-2 bg-light rounded border">
                    <span class="text-muted d-block" style="font-size: 0.65rem;">Distributor</span>
                    <strong class="fs-6 text-dark">{{ $counts['distributors'] }}</strong>
                </div>
                <div class="col-3 p-2 bg-light rounded border">
                    <span class="text-muted d-block" style="font-size: 0.65rem;">Agen Kec.</span>
                    <strong class="fs-6 text-dark">{{ $counts['agents'] }}</strong>
                </div>
                <div class="col-3 p-2 bg-light rounded border">
                    <span class="text-muted d-block" style="font-size: 0.65rem;">Sub-Agen Desa</span>
                    <strong class="fs-6 text-dark">{{ $counts['sub_agents'] }}</strong>
                </div>
                <div class="col-3 p-2 bg-light rounded border">
                    <span class="text-muted d-block" style="font-size: 0.65rem;">Kurir Ojol</span>
                    <strong class="fs-6 text-dark">{{ $counts['drivers'] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Mutasi Double-Entry Ledger Koperasi -->
    <div class="card border mb-3 shadow-sm">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 small text-dark">
                <i class="bi bi-journal-text text-success me-1"></i> Audit Trail Mutasi Dompet ({{ $latestMutations->count() }})
            </h6>
            <span class="badge bg-light text-secondary border small" style="font-size: 0.65rem;">Anti-Tamper</span>
        </div>
        <div class="card-body p-2">
            @if($latestMutations->isEmpty())
                <div class="text-center py-3 text-muted small">Belum ada catatan mutasi dompet.</div>
            @else
                <div class="d-flex flex-column gap-2">
                    @foreach($latestMutations as $mut)
                        <div class="p-2 rounded border bg-light small" style="font-size: 0.73rem;">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <strong class="text-dark">{{ $mut->wallet->user->name ?? 'User' }}</strong>
                                    <small class="text-muted d-block" style="font-size: 0.65rem;">{{ $mut->created_at->format('d/m/Y H:i:s') }}</small>
                                </div>
                                <span class="badge bg-success text-white">
                                    +Rp {{ number_format($mut->amount, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="text-secondary" style="font-size: 0.7rem;">
                                {{ $mut->description }}
                            </div>
                            <div class="text-muted pt-1 mt-1 border-top" style="font-size: 0.65rem;">
                                Saldo: Rp {{ number_format($mut->balance_before, 0, ',', '.') }} &rarr; 
                                <strong>Rp {{ number_format($mut->balance_after, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Navigasi Pintas -->
    <div class="d-grid gap-2 mb-3">
        <a href="{{ route('merchant.index') }}" class="btn btn-outline-dark py-2 small fw-semibold">
            <i class="bi bi-shop me-1"></i> Masuk ke Portal Toko Mitra
        </a>
        <a href="{{ route('storefront.index') }}" class="btn btn-success py-2 small fw-semibold">
            <i class="bi bi-house-door me-1"></i> Buka Storefront Belanja
        </a>
    </div>
</div>
@endsection
