@extends('layouts.backoffice')

@section('title', 'Portal Super Admin & Finance - Mak Ethek Kota Blitar')
@section('page_heading', 'Executive Super Admin & Financial Reconciliation — Pilot Kota Blitar')
@section('page_subheading', 'Pemisahan GMV vs Revenue • Target B2C:B2B (90:10) • Margin Kontribusi • Rekonsiliasi Settlement DOKU • Audit Log')

@section('content')
<!-- Filter Bar & Ekspor Laporan (SA-006) -->
<div class="table-card p-3 mb-4 bg-white">
    <form method="GET" action="{{ route('finance.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Filter Kanal Transaksi</label>
            <select name="channel" class="form-select form-select-sm">
                <option value="all" {{ $channelFilter === 'all' ? 'selected' : '' }}>Semua Kanal (B2C + B2B)</option>
                <option value="b2c" {{ $channelFilter === 'b2c' ? 'selected' : '' }}>Khusus Retail Warga (B2C)</option>
                <option value="b2b" {{ $channelFilter === 'b2b' ? 'selected' : '' }}>Khusus Grosir Warung (B2B ≥ Rp1 Jt)</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
            <input type="date" name="from" class="form-control form-control-sm" value="{{ $dateFrom }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
            <input type="date" name="to" class="form-control form-control-sm" value="{{ $dateTo }}">
        </div>
        <div class="col-md-5 d-flex gap-2 justify-content-end">
            <button type="submit" class="btn btn-sm btn-dark px-3">
                <i class="bi bi-funnel me-1"></i> Terapkan Filter
            </button>
            <a href="{{ route('finance.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            <a href="{{ route('finance.exportCsv') }}" class="btn btn-sm btn-success fw-semibold">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Ekspor CSV (SA-006)
            </a>
        </div>
    </form>
</div>

<!-- Baris 1: 4 Kartu Metrik Utama (GMV, Revenue, Gross Margin, Contribution Margin - PRD Bab 12) -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="kpi-card border-top border-4 border-primary">
            <small class="text-muted fw-bold d-block">1. TOTAL GMV (PAID/VALID)</small>
            <h4 class="fw-bold text-dark mt-1 mb-1">Rp {{ number_format($gmvTotal, 0, ',', '.') }}</h4>
            <div class="d-flex justify-content-between small text-muted">
                <span>Total Order: <strong>{{ $totalOrderCount }}</strong></span>
                <span>AOV: <strong>Rp {{ number_format($aov, 0, ',', '.') }}</strong></span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card border-top border-4 border-success">
            <small class="text-muted fw-bold d-block">2. REVENUE PENJUALAN & HPP</small>
            <h4 class="fw-bold text-success mt-1 mb-1">Rp {{ number_format($revenueSales, 0, ',', '.') }}</h4>
            <small class="text-muted d-block" style="font-size: 0.75rem;">
                HPP Barang: <strong>Rp {{ number_format($totalHpp, 0, ',', '.') }}</strong>
            </small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card border-top border-4 border-info">
            <small class="text-muted fw-bold d-block">3. GROSS MARGIN (LABA KOTOR)</small>
            <h4 class="fw-bold text-info mt-1 mb-1">Rp {{ number_format($grossMargin, 0, ',', '.') }}</h4>
            <small class="text-muted d-block" style="font-size: 0.75rem;">
                Margin Kotor: <strong>{{ $grossMarginPct }}%</strong> dari Revenue
            </small>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card border-top border-4 border-warning">
            <small class="text-muted fw-bold d-block">4. CONTRIBUTION MARGIN</small>
            <h4 class="fw-bold text-dark mt-1 mb-1">Rp {{ number_format($contributionMargin, 0, ',', '.') }}</h4>
            <small class="text-muted d-block" style="font-size: 0.72rem;">
                Setelah Fee DOKU (Rp {{ number_format($dokuFeeTotal, 0, ',', '.') }}), KurirQ & Handling
            </small>
        </div>
    </div>
</div>

<!-- Baris 2: Komposisi B2C:B2B (90:10), Biaya Variabel, & Rasio Keberhasilan -->
<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold small text-uppercase text-secondary">Komposisi Order B2C vs B2B (BR-003: Target 90:10)</span>
                <span class="badge bg-dark">Aktual: {{ $b2cPct }}% : {{ $b2bPct }}%</span>
            </div>
            <div class="progress mb-2" style="height: 12px;">
                <div class="progress-bar bg-success" style="width: {{ $b2cPct }}%" title="B2C"></div>
                <div class="progress-bar bg-primary" style="width: {{ $b2bPct }}%" title="B2B"></div>
            </div>
            <div class="row g-2 small">
                <div class="col-6">
                    <span class="badge bg-success me-1">B2C</span> <strong>{{ $b2cCount }} Order</strong> (Rp {{ number_format($b2cGmv, 0, ',', '.') }})
                </div>
                <div class="col-6 text-end">
                    <span class="badge bg-primary me-1">B2B</span> <strong>{{ $b2bCount }} Order</strong> (Rp {{ number_format($b2bGmv, 0, ',', '.') }})
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="kpi-card">
            <span class="fw-bold small text-uppercase text-secondary d-block mb-2">Rincian Biaya Operasional & Provider (SA-002)</span>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Biaya Pengiriman KurirQ:</span>
                <strong class="text-dark">Rp {{ number_format($courierCostTotal, 0, ',', '.') }}</strong>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Biaya Gateway DOKU:</span>
                <strong class="text-dark">Rp {{ number_format($dokuFeeTotal, 0, ',', '.') }}</strong>
            </div>
            <div class="d-flex justify-content-between small">
                <span class="text-muted">Biaya Handling Gudang:</span>
                <strong class="text-dark">Rp {{ number_format($handlingCostTotal, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card">
            <span class="fw-bold small text-uppercase text-secondary d-block mb-2">Keandalan Pembayaran & Kurir</span>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-muted">Payment Success Rate:</span>
                <span class="badge bg-success">{{ $paymentSuccessRate }}%</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted">Delivery Success Rate:</span>
                <span class="badge bg-primary">{{ $deliverySuccessRate }}%</span>
            </div>
        </div>
    </div>
</div>

<!-- SEKSI 1: REKONSILIASI SETTLEMENT DOKU & REFUND (SA-003, SA-004, PAY-006) -->
<div id="section-doku" class="table-card mb-4">
    <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0"><i class="bi bi-credit-card-2-front-fill text-primary me-2"></i>Rekonsiliasi Transaksi & Settlement DOKU (SA-003, SA-004)</h6>
            <small class="text-muted">Cocokkan pembayaran DOKU (QRIS, ShopeePay, VA) dengan order & proses pengajuan Refund dengan Audit Log.</small>
        </div>
        <span class="badge bg-light text-dark border">{{ $payments->count() }} Transaksi DOKU</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.83rem;">
            <thead class="table-light">
                <tr>
                    <th>Payment Ref DOKU</th>
                    <th>No. Order & Pelanggan</th>
                    <th>Kanal</th>
                    <th class="text-end">Nominal</th>
                    <th class="text-end">Fee DOKU</th>
                    <th class="text-center">Status Bayar</th>
                    <th class="text-center">Settlement</th>
                    <th>Tindakan Rekonsiliasi & Refund (Finance / Superadmin)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $pay)
                    <tr>
                        <td class="font-monospace fw-semibold">
                            {{ $pay->payment_reference }}
                            @if($pay->signature_verified)
                                <i class="bi bi-patch-check-fill text-success" title="Webhook Signature Verified"></i>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $pay->order->order_number ?? '-' }}</div>
                            <small class="text-muted">{{ $pay->order->customer->name ?? '-' }}</small>
                        </td>
                        <td><span class="badge bg-secondary text-uppercase">{{ $pay->channel }}</span></td>
                        <td class="text-end fw-bold">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                        <td class="text-end text-muted">Rp {{ number_format($pay->provider_fee, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($pay->status === 'paid')
                                <span class="badge bg-success">PAID</span>
                            @elseif($pay->status === 'refunded')
                                <span class="badge bg-danger">REFUNDED</span>
                            @elseif($pay->status === 'expired')
                                <span class="badge bg-secondary">EXPIRED</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ strtoupper($pay->status) }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($pay->settlement_status === 'settled')
                                <span class="badge bg-success"><i class="bi bi-check2-all"></i> SETTLED</span>
                                <div class="text-muted font-monospace" style="font-size: 0.68rem;">{{ $pay->settlement_reference }}</div>
                            @elseif($pay->settlement_status === 'mismatch')
                                <span class="badge bg-danger">MISMATCH</span>
                            @else
                                <span class="badge bg-light text-dark border">UNSETTLED</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                @if($pay->status === 'paid' && $pay->settlement_status !== 'settled')
                                    <form action="{{ route('finance.reconcile', $pay->id) }}" method="POST" class="d-flex gap-1">
                                        @csrf
                                        <input type="hidden" name="settlement_status" value="settled">
                                        <input type="text" name="settlement_reference" class="form-control form-control-sm"
                                               placeholder="No. Batch Settlement" style="width: 145px;">
                                        <button type="submit" class="btn btn-sm btn-success text-nowrap">
                                            <i class="bi bi-check2-circle"></i> Settle
                                        </button>
                                    </form>
                                @endif

                                @if($pay->status === 'paid')
                                    <form action="{{ route('finance.refund', $pay->id) }}" method="POST" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="refund_reason" class="form-control form-control-sm"
                                               placeholder="Alasan Refund wajib" style="width: 145px;" required>
                                        <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                                            <i class="bi bi-arrow-counterclockwise"></i> Refund
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada transaksi pembayaran DOKU yang tercatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- SEKSI 2: VERIFIKASI AKUN PELANGGAN B2B GROSIR WARUNG (B2B-001) -->
<div id="section-b2b" class="table-card mb-4">
    <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0"><i class="bi bi-shop text-success me-2"></i>Manajemen & Verifikasi Akun Pelanggan B2B Grosir (B2B-001 s/d B2B-005)</h6>
            <small class="text-muted">Akun B2B yang disetujui dapat melihat harga grosir koperasi dengan validasi minimum belanja Rp 1.000.000 sebelum ongkir.</small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.84rem;">
            <thead class="table-light">
                <tr>
                    <th>Nama Pelanggan</th>
                    <th>Email / No. HP</th>
                    <th>Wilayah Domisili</th>
                    <th>Tipe Akun Saat Ini</th>
                    <th>Nama Warung B2B</th>
                    <th>Ubah Status Verifikasi B2B</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $cust)
                    <tr>
                        <td class="fw-semibold">{{ $cust->name }}</td>
                        <td>{{ $cust->email }}<br><small class="text-muted">{{ $cust->phone }}</small></td>
                        <td>{{ $cust->territory->name ?? 'Kota Blitar' }}</td>
                        <td>
                            @if($cust->isB2BCustomer())
                                <span class="badge bg-primary">MITRA GROSIR B2B (Verified)</span>
                            @else
                                <span class="badge bg-secondary">RETAIL WARGA (B2C)</span>
                            @endif
                        </td>
                        <td>{{ $cust->b2b_store_name ?? '-' }}</td>
                        <td>
                            <form action="{{ route('finance.toggleB2B', $cust->id) }}" method="POST" class="d-flex gap-1 align-items-center">
                                @csrf
                                <input type="text" name="b2b_store_name" class="form-control form-control-sm"
                                       placeholder="Nama Warung/Toko B2B" value="{{ $cust->b2b_store_name }}" style="width: 180px;">
                                <button type="submit" class="btn btn-sm {{ $cust->isB2BCustomer() ? 'btn-outline-secondary' : 'btn-primary' }}">
                                    {{ $cust->isB2BCustomer() ? 'Ubah ke B2C' : 'Verifikasi Jadi B2B' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- SEKSI 3: AUDIT LOG KEAMANAN & TRANSAKSI (SEC-005, BR-015) -->
<div id="section-audit" class="table-card">
    <div class="p-3 bg-white border-bottom">
        <h6 class="fw-bold mb-0"><i class="bi bi-shield-lock-fill text-dark me-2"></i>Rekam Jejak Audit Sistem (Security & Financial Audit Logs — SEC-005)</h6>
        <small class="text-muted">Merekam seluruh aktivitas perubahan harga, adjustment stok, status order, settlement DOKU, refund, dan verifikasi B2B.</small>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0" style="font-size: 0.81rem;">
            <thead class="table-light">
                <tr>
                    <th>Timestamp</th>
                    <th>Aktor & Role</th>
                    <th>Aksi</th>
                    <th>Entitas</th>
                    <th>Perubahan Data (Before &rarr; After)</th>
                    <th>Alasan / Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLogs as $log)
                    <tr>
                        <td class="text-muted">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $log->actor->name ?? 'System/Webhook' }}</div>
                            <span class="badge bg-light text-dark border" style="font-size: 0.65rem;">{{ strtoupper($log->actor_role ?? 'SYSTEM') }}</span>
                        </td>
                        <td><code class="fw-bold text-primary">{{ $log->action }}</code></td>
                        <td>{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                        <td>
                            <small class="font-monospace d-block text-muted">
                                @if($log->before_state)
                                    Before: {{ json_encode($log->before_state) }}<br>
                                @endif
                                @if($log->after_state)
                                    After: {{ json_encode($log->after_state) }}
                                @endif
                            </small>
                        </td>
                        <td>{{ $log->reason ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada entri audit log.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

