@extends('layouts.merchant')

@section('title', 'Dashboard Mitra - ' . $merchant->store_name)

@section('header_extra')
    <!-- Switcher Akun Mitra Demo (Sub-Agen vs Agen vs Distributor) -->
    <div class="mt-2 pt-2 border-top border-white-50">
        <form action="{{ route('merchant.switch') }}" method="POST" class="d-flex align-items-center gap-2">
            @csrf
            <span style="font-size: 0.72rem; white-space: nowrap;">Peran Aktif:</span>
            <select name="merchant_id" class="form-select form-select-sm bg-white text-dark py-0" style="font-size: 0.75rem;" onchange="this.form.submit()">
                @foreach($allMerchants as $m)
                    <option value="{{ $m->id }}" {{ $merchant->id == $m->id ? 'selected' : '' }}>
                        [{{ strtoupper($m->tier) }}] {{ $m->store_name }} ({{ $m->territory->name }})
                    </option>
                @endforeach
            </select>
        </form>
    </div>
@endsection

@section('content')
<div class="p-3">
    <!-- Profil Toko & Status Buka/Tutup -->
    <div class="card border mb-3 shadow-sm">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <span class="badge bg-warning text-dark border border-warning px-2 py-1 mb-1 fw-bold" style="font-size: 0.65rem;">
                        MITRA {{ strtoupper($merchant->tier) }}
                    </span>
                    <h5 class="fw-bold text-dark mb-0 small">{{ $merchant->store_name }}</h5>
                    <small class="text-muted" style="font-size: 0.72rem;">
                        <i class="bi bi-geo-alt"></i> {{ $merchant->territory?->name }} ({{ $merchant->address }})
                    </small>
                </div>

                <form action="{{ route('merchant.toggleStatus') }}" method="POST">
                    @csrf
                    @if($merchant->is_open)
                        <button type="submit" class="btn btn-sm btn-success py-1 px-2" style="font-size: 0.72rem;">
                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> BUKA
                        </button>
                    @else
                        <button type="submit" class="btn btn-sm btn-danger py-1 px-2" style="font-size: 0.72rem;">
                            <i class="bi bi-dash-circle me-1"></i> TUTUP
                        </button>
                    @endif
                </form>
            </div>

            <div class="d-flex justify-content-between pt-2 border-top text-secondary small" style="font-size: 0.75rem;">
                <span>Min. Belanja: <strong>Rp {{ number_format($merchant->min_order_amount, 0, ',', '.') }}</strong></span>
                <span>Radius Antar: <strong>{{ $merchant->delivery_radius_km }} km</strong></span>
                <a href="{{ route('merchant.profile') }}" class="text-success text-decoration-none fw-semibold">
                    <i class="bi bi-gear"></i> Ubah
                </a>
            </div>
        </div>
    </div>

    <!-- Ringkasan Metrik Toko -->
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card border shadow-sm p-2 text-center bg-white">
                <small class="text-muted d-block" style="font-size: 0.65rem;">Omset Hari Ini</small>
                <span class="fw-bold text-success" style="font-size: 0.78rem;">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="col-4">
            <a href="{{ route('merchant.orders', ['status' => 'pending']) }}" class="card border shadow-sm p-2 text-center text-decoration-none bg-white">
                <small class="text-muted d-block" style="font-size: 0.65rem;">Perlu Diproses</small>
                <span class="fw-bold text-warning-emphasis" style="font-size: 0.85rem;">{{ $pendingOrdersCount }} Baru</span>
            </a>
        </div>
        <div class="col-4">
            <a href="{{ route('merchant.orders', ['status' => 'processing']) }}" class="card border shadow-sm p-2 text-center text-decoration-none bg-white">
                <small class="text-muted d-block" style="font-size: 0.65rem;">Pesanan Aktif</small>
                <span class="fw-bold text-primary" style="font-size: 0.85rem;">{{ $activeOrdersCount }} Aktif</span>
            </a>
        </div>
    </div>

    <!-- Dompet Internal Koperasi (Holding Double-Entry Balance) -->
    <div class="card border mb-3 shadow-sm bg-light">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-bold small text-dark d-flex align-items-center gap-1">
                    <i class="bi bi-wallet2 text-success"></i> Saldo Dompet Mitra
                </span>
                <a href="{{ route('merchant.finance') }}" class="badge bg-white text-success border text-decoration-none" style="font-size: 0.68rem;">
                    Laporan Keuangan &rarr;
                </a>
            </div>
            
            <div class="d-flex align-items-baseline gap-2 mb-2">
                <h4 class="fw-bold text-success mb-0">
                    Rp {{ number_format($wallet->balance_available, 0, ',', '.') }}
                </h4>
                <small class="text-muted">Siap Dicairkan</small>
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 flex-grow-1" style="font-size: 0.75rem;"
                        onclick="alert('Fitur Payout / Batch Disbursement API Koperasi (Flip/Midtrans Iris) akan mentransfer saldo ini langsung ke rekening bank pemilik toko secara terjadwal.');">
                    <i class="bi bi-cash-stack me-1"></i> Tarik Dana
                </button>
                <a href="{{ route('merchant.procurement') }}" class="btn btn-sm btn-success py-1 px-2 flex-grow-1" style="font-size: 0.75rem;">
                    <i class="bi bi-boxes me-1"></i> Kulakan B2B
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Stok Menipis (Bila Ada) -->
    @if($lowStocks->isNotEmpty())
        <div class="alert alert-warning py-2 px-3 mb-3 small d-flex align-items-center justify-content-between">
            <div>
                <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                <strong>{{ $lowStocks->count() }} produk</strong> stoknya menipis (&le; 10 unit).
            </div>
            <a href="{{ route('merchant.procurement') }}" class="btn btn-sm btn-warning py-0 px-2 fw-semibold" style="font-size: 0.72rem;">
                Kulakan
            </a>
        </div>
    @endif

    <!-- Daftar Pesanan Retail Masuk -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold text-dark mb-0 small">
            <i class="bi bi-inbox-fill text-primary me-1"></i> Pesanan Retail Warga Masuk
        </h6>
        <a href="{{ route('merchant.orders') }}" class="text-success small fw-semibold text-decoration-none" style="font-size: 0.72rem;">
            Lihat Semua Pesanan &rarr;
        </a>
    </div>

    @php $displayOrders = $recentOrders ?? $retailOrders; @endphp
    @if($displayOrders->isEmpty())
        <div class="p-4 rounded-3 border bg-white text-center text-muted mb-3">
            <i class="bi bi-receipt-cutoff fs-2 d-block mb-1 opacity-50"></i>
            <span class="small">Belum ada pesanan retail masuk.</span>
        </div>
    @else
        <div class="d-flex flex-column gap-3 mb-3">
            @foreach($displayOrders as $order)
                <div class="card border shadow-sm">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold font-monospace small text-dark">{{ $order->order_number }}</span>
                            <small class="text-muted d-block" style="font-size: 0.68rem;">{{ $order->created_at->diffForHumans() }}</small>
                        </div>
                        
                        @if($order->status === 'pending')
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">Menunggu</span>
                        @elseif($order->status === 'processing')
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle small">Dikemas</span>
                        @elseif($order->status === 'ready_to_ship')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">Siap Kirim</span>
                        @elseif($order->status === 'delivered')
                            <span class="badge bg-success-subtle text-success border border-success-subtle small">Selesai & Cair</span>
                        @endif
                    </div>

                    <div class="card-body p-3">
                        <div class="mb-2">
                            <span class="text-muted small" style="font-size: 0.72rem;">Pelanggan:</span>
                            <div class="fw-bold small text-dark">{{ $order->customer->name ?? 'Warga' }} ({{ $order->customer->phone ?? '-' }})</div>
                            <div class="text-muted" style="font-size: 0.72rem;">{{ $order->shipping_address }}</div>
                        </div>

                        <!-- Daftar Item -->
                        <div class="p-2 bg-light rounded border mb-2 small" style="font-size: 0.75rem;">
                            @foreach($order->items as $it)
                                <div class="d-flex justify-content-between">
                                    <span>{{ $it->product_name }} &times; {{ $it->quantity }} {{ $it->unit_name }}</span>
                                    <strong>Rp {{ number_format($it->subtotal, 0, ',', '.') }}</strong>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-between pt-1 mt-1 border-top fw-bold text-dark">
                                <span>Total Belanja:</span>
                                <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- Form Aksi Pesanan -->
                        @if($order->status === 'pending')
                            <form action="{{ route('merchant.updateOrderStatus', $order->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold py-1">
                                    <i class="bi bi-box-seam me-1"></i> Konfirmasi & Kemas Barang
                                </button>
                            </form>
                        @elseif($order->status === 'processing')
                            <form action="{{ route('merchant.updateOrderStatus', $order->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="ready">
                                <button type="submit" class="btn btn-sm btn-info text-white w-100 fw-semibold py-1">
                                    <i class="bi bi-truck me-1"></i> Siap Diantar Kurir / Ambil
                                </button>
                            </form>
                        @elseif($order->status === 'ready_to_ship' || $order->status === 'on_delivery')
                            <!-- Form Masukkan OTP Pelanggan untuk Klaim Escrow -->
                            <form action="{{ route('merchant.updateOrderStatus', $order->id) }}" method="POST" class="p-2 bg-white rounded border border-success">
                                @csrf
                                <input type="hidden" name="action" value="complete">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold text-success mb-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-key-fill me-1"></i> Masukkan 4-Digit OTP Serah-Terima Pelanggan:
                                    </label>
                                    <input type="text" name="otp" class="form-control form-control-sm text-center font-monospace fw-bold" 
                                           placeholder="Contoh: {{ $order->delivery->completion_otp ?? '1234' }}" required maxlength="6">
                                </div>
                                <button type="submit" class="btn btn-sm btn-success w-100 fw-semibold py-1">
                                    <i class="bi bi-check2-circle me-1"></i> Verifikasi OTP & Cairkan Saldo Rp {{ number_format($order->subtotal, 0, ',', '.') }}
                                </button>
                            </form>
                        @elseif($order->status === 'delivered')
                            <div class="text-success small fw-semibold text-center py-1">
                                <i class="bi bi-check-circle-fill me-1"></i> Pesanan Selesai. Hasil penjualan telah masuk ke saldo dompet.
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
