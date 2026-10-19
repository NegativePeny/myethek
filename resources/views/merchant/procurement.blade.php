@extends('layouts.merchant')

@section('title', 'Kulakan Grosir B2B - ' . $merchant->store_name)

@section('content')
<div class="p-3">
    <!-- Banner Aturan Pasokan Wilayah -->
    <div class="p-3 mb-3 rounded-3 bg-light border">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-diagram-3-fill text-success fs-5"></i>
            <h6 class="fw-bold mb-0 text-dark small">Alur Pasokan Koperasi Terikat Wilayah</h6>
        </div>
        <p class="text-secondary small mb-2" style="font-size: 0.75rem;">
            Sesuai regulasi Koperasi Jatim, 
            <strong>{{ strtoupper($merchant->tier) }}</strong> terikat memesan pasokan kulakan secara eksklusif ke mitra tingkat atasnya.
        </p>

        @if($supplier)
            <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted" style="font-size: 0.68rem;">Pemasok Resmi Anda:</span>
                    <div class="fw-bold small text-dark">{{ $supplier->store_name }}</div>
                    <small class="text-muted" style="font-size: 0.7rem;">
                        {{ strtoupper($supplier->tier) }} • {{ $supplier->territory?->name }}
                    </small>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle small">
                    Terverifikasi
                </span>
            </div>
        @else
            <div class="alert alert-warning mb-0 py-2 small">
                Belum ada mitra pemasok resmi terdaftar untuk tingkatan ini.
            </div>
        @endif
    </div>

    <!-- Form Katalog Kulakan Grosir -->
    @if($supplier && !empty($catalog))
        <div class="card border mb-3 shadow-sm">
            <div class="card-header bg-white py-2">
                <h6 class="fw-bold mb-0 small text-dark">
                    <i class="bi bi-cart-plus text-primary me-1"></i> Form Order Kulakan Grosir
                </h6>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('merchant.orderProcurement') }}" method="POST">
                    @csrf
                    <div class="d-flex flex-column gap-3 mb-3">
                        @foreach($catalog as $item)
                            @php
                                $p = $item['product'];
                            @endphp
                            <div class="p-2 rounded border bg-light">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div>
                                        <div class="fw-bold text-dark small">{{ $p->name }}</div>
                                        <span class="badge bg-white text-secondary border small" style="font-size: 0.68rem;">
                                            Satuan: {{ $p->unit_name }}
                                        </span>
                                    </div>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle small" style="font-size: 0.68rem;">
                                        MOQ: {{ $item['moq'] }} {{ $p->unit_name }}
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top" style="font-size: 0.75rem;">
                                    <div>
                                        <div class="text-muted">Harga Kulakan:</div>
                                        <strong class="text-success">Rp {{ number_format($item['wholesale_price'], 0, ',', '.') }}</strong>
                                        <small class="text-muted d-block">Eceran: Rp {{ number_format($item['retail_base_price'], 0, ',', '.') }}</small>
                                    </div>

                                    <div class="d-flex align-items-center gap-1">
                                        <label for="p_{{ $p->id }}" class="text-muted" style="font-size: 0.7rem;">Pesan:</label>
                                        <input type="number" id="p_{{ $p->id }}" name="items[{{ $p->id }}]" 
                                               class="form-control form-control-sm text-center" 
                                               placeholder="0" min="0" step="1" style="width: 75px;">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success fw-bold py-2">
                            <i class="bi bi-send-check me-1"></i> Ajukan Purchase Order (PO) Kulakan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Riwayat Order Kulakan Toko Ini -->
    <div class="card border mb-3 shadow-sm">
        <div class="card-header bg-white py-2">
            <h6 class="fw-bold mb-0 small text-dark">
                <i class="bi bi-clock-history text-secondary me-1"></i> Riwayat Kulakan B2B ({{ $procurementOrders->count() }})
            </h6>
        </div>
        <div class="card-body p-3">
            @if($procurementOrders->isEmpty())
                <div class="text-center py-3 text-muted small">
                    Belum ada riwayat order kulakan yang dibuat.
                </div>
            @else
                <div class="d-flex flex-column gap-2">
                    @foreach($procurementOrders as $po)
                        <div class="p-2 rounded border bg-white small">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <span class="fw-bold font-monospace text-dark">{{ $po->order_number }}</span>
                                    <small class="text-muted d-block" style="font-size: 0.68rem;">{{ $po->created_at->format('d M Y, H:i') }}</small>
                                </div>
                                @if($po->status === 'completed' || $po->status === 'delivered')
                                    <span class="badge bg-success text-white" style="font-size: 0.65rem;">Diterima (Stok Masuk)</span>
                                @else
                                    <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">Sedang Diproses</span>
                                @endif
                            </div>

                            <div class="text-muted mb-2" style="font-size: 0.72rem;">
                                Pemasok: <strong>{{ $po->merchant->store_name }}</strong> • Total: <strong>Rp {{ number_format($po->total_amount, 0, ',', '.') }}</strong>
                            </div>

                            <!-- Tombol Konfirmasi Terima Barang Kulakan -->
                            @if($po->status !== 'completed' && $po->status !== 'delivered')
                                <form action="{{ route('merchant.receiveProcurement', $po->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success w-100 py-1 fw-semibold" style="font-size: 0.72rem;">
                                        <i class="bi bi-box-arrow-in-down me-1"></i> Konfirmasi Barang Tiba (Tambah Stok Otomatis)
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
