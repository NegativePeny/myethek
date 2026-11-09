@extends('layouts.backoffice')

@section('title', 'Overview Operasional & Holding Koperasi — Mak Ethek Backoffice')
@section('page_title', 'Overview Operasional & Manajemen Pesanan')
@section('page_subtitle', 'PERSATUAN KOPERASI JAWA TIMUR • Financial Holding & Territory Ledger (Pilot Kota Blitar)')

@section('header_actions')
    <a href="{{ route('warehouse.index') }}" class="btn btn-sm btn-outline-success fw-semibold">
        <i class="bi bi-boxes me-1"></i> Buka WMS Gudang
    </a>
    <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-success fw-bold">
        <i class="bi bi-plus-lg me-1"></i> Tambah Master Produk
    </a>
@endsection

@section('content')
<!-- Banner Identitas Koperasi -->
<div class="p-3 mb-4 rounded-3 d-flex flex-wrap align-items-center justify-content-between gap-3" style="background: linear-gradient(135deg, #0f172a, #1e293b); color: white;">
    <div>
        <span class="badge bg-success text-white fw-bold mb-1" style="font-size: 0.7rem;">
            PERSATUAN KOPERASI JAWA TIMUR
        </span>
        <h5 class="fw-bold mb-1">Financial Holding & Territory Ledger</h5>
        <p class="mb-0 text-white-50 small">
            Konsolidasi transaksi perdagangan Pilot Kota Blitar (Satu Koperasi • Satu Gudang Utama • KurirQ Lokal).
        </p>
    </div>
    <div class="d-flex gap-2">
        <div class="px-3 py-2 rounded bg-white bg-opacity-10 text-center">
            <small class="d-block text-white-50" style="font-size: 0.68rem;">Mitra Toko</small>
            <strong class="fs-6">{{ $counts['distributors'] + $counts['agents'] + $counts['sub_agents'] }}</strong>
        </div>
        <div class="px-3 py-2 rounded bg-white bg-opacity-10 text-center">
            <small class="d-block text-white-50" style="font-size: 0.68rem;">Driver KurirQ</small>
            <strong class="fs-6">{{ $counts['drivers'] }}</strong>
        </div>
    </div>
</div>

<!-- 4 Kartu Metrik Keuangan Utama -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <small class="text-muted fw-bold d-block" style="font-size: 0.72rem;">GMV RETAIL DESA:</small>
            <h4 class="fw-bold text-success mb-0 mt-1">Rp {{ number_format($retailGmv, 0, ',', '.') }}</h4>
            <small class="text-muted">Belanja Warga (B2C & Grosir)</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <small class="text-muted fw-bold d-block" style="font-size: 0.72rem;">KULAKAN GROSIR B2B:</small>
            <h4 class="fw-bold text-primary mb-0 mt-1">Rp {{ number_format($procurementGmv, 0, ',', '.') }}</h4>
            <small class="text-muted">Pasokan Berjenjang Mitra</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <small class="text-muted fw-bold d-block" style="font-size: 0.72rem;">ESCROW TERTAHAN:</small>
            <h4 class="fw-bold text-warning mb-0 mt-1">Rp {{ number_format($escrowHeld, 0, ',', '.') }}</h4>
            <small class="text-muted">Status Held (Dalam Pengiriman)</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <small class="text-muted fw-bold d-block" style="font-size: 0.72rem;">FEE PLATFORM KOPERASI:</small>
            <h4 class="fw-bold text-dark mb-0 mt-1">Rp {{ number_format($platformFeeTotal, 0, ',', '.') }}</h4>
            <small class="text-muted">Pendapatan Layanan Koperasi</small>
        </div>
    </div>
</div>

<!-- Tabel Manajemen Pesanan Masuk & Penugasan KurirQ (ADM-003, ADM-006) -->
@if(isset($recentOrders))
<div class="table-card mb-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0 text-dark">
                <i class="bi bi-cart-check text-success me-1"></i> Manajemen Pesanan Masuk & Penugasan Driver KurirQ
            </h6>
            <small class="text-muted">Terima/tolak pesanan dengan alasan wajib (ADM-003) dan tugaskan driver KurirQ (ADM-006)</small>
        </div>
        <a href="{{ route('warehouse.index') }}" class="btn btn-sm btn-light border">
            Ke Antrean Picking Gudang &rarr;
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">No. Order</th>
                    <th>Pelanggan & Penerima</th>
                    <th>Tipe</th>
                    <th>Total & Bayar</th>
                    <th>Status Order</th>
                    <th>Driver KurirQ</th>
                    <th class="pe-3 text-end">Tindakan Admin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $ord)
                    <tr>
                        <td class="ps-3">
                            <span class="fw-bold font-monospace text-dark">{{ $ord->order_number }}</span>
                            <small class="d-block text-muted">{{ $ord->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $ord->recipient_name ?? $ord->customer?->name }}</div>
                            <small class="text-muted d-block">{{ $ord->recipient_phone ?? $ord->customer?->phone }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $ord->order_type === 'b2b' ? 'bg-warning text-dark' : 'bg-info-subtle text-info-emphasis' }}">
                                {{ strtoupper($ord->order_type ?? 'b2c') }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-bold text-success">Rp {{ number_format($ord->total_amount, 0, ',', '.') }}</div>
                            <small class="text-muted">{{ strtoupper($ord->payment_method) }} • {{ strtoupper($ord->payment_status) }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ strtoupper($ord->status) }}</span>
                            @if($ord->cancellation_reason)
                                <small class="d-block text-danger" style="font-size: 0.7rem;">Alasan: {{ $ord->cancellation_reason }}</small>
                            @endif
                        </td>
                        <td>
                            @if($ord->delivery?->driver)
                                <div class="fw-semibold small text-dark">{{ $ord->delivery->driver->name }}</div>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">{{ strtoupper($ord->delivery->status) }}</span>
                            @elseif(isset($drivers) && $drivers->isNotEmpty() && !in_array($ord->status, ['completed', 'cancelled']))
                                <form action="{{ route('admin.orders.assignDriver', $ord->id) }}" method="POST" class="d-flex gap-1">
                                    @csrf
                                    <select name="driver_id" class="form-select form-select-sm" style="width: 150px; font-size: 0.75rem;" required>
                                        <option value="">-- Pilih KurirQ --</option>
                                        @foreach($drivers as $drv)
                                            <option value="{{ $drv->id }}">{{ $drv->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary py-1 px-2" title="Tugaskan Driver">
                                        <i class="bi bi-person-check"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="pe-3 text-end">
                            @if(in_array($ord->status, ['pending', 'paid', 'confirmed']))
                                <div class="d-inline-flex gap-1">
                                    <form action="{{ route('admin.orders.decision', $ord->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="decision" value="accept">
                                        <button type="submit" class="btn btn-sm btn-success fw-semibold">
                                            <i class="bi bi-check-lg"></i> Terima
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#rejectAdmin{{ $ord->id }}">
                                        Tolak
                                    </button>
                                </div>
                                <div class="collapse mt-2 text-start" id="rejectAdmin{{ $ord->id }}">
                                    <form action="{{ route('admin.orders.decision', $ord->id) }}" method="POST" class="p-2 bg-light rounded border">
                                        @csrf
                                        <input type="hidden" name="decision" value="reject">
                                        <input type="text" name="reason" class="form-control form-control-sm mb-1" placeholder="Alasan penolakan wajib..." required>
                                        <button type="submit" class="btn btn-xs btn-danger w-100 py-1" style="font-size: 0.72rem;">Konfirmasi Tolak</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted small">Terproses</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada pesanan masuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Audit Mutasi Double-Entry Ledger Koperasi -->
<div class="table-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="bi bi-journal-text text-success me-1"></i> Audit Trail Mutasi Dompet ({{ $latestMutations->count() }})
        </h6>
        <span class="badge bg-light text-secondary border small">Anti-Tamper Double-Entry Ledger</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Waktu</th>
                    <th>Pemilik Dompet</th>
                    <th>Deskripsi Mutasi</th>
                    <th class="text-end">Nominal</th>
                    <th class="pe-3 text-end">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($latestMutations as $mut)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $mut->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="fw-semibold text-dark">{{ $mut->wallet->user->name ?? 'User' }}</td>
                        <td class="text-secondary small">{{ $mut->description }}</td>
                        <td class="text-end">
                            <span class="badge bg-success-subtle text-success fw-bold">
                                +Rp {{ number_format($mut->amount, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="pe-3 text-end fw-bold text-dark">
                            Rp {{ number_format($mut->balance_after, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada catatan mutasi dompet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
