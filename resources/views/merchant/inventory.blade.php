@extends('layouts.merchant')

@section('title', 'Manajemen Stok Fisik - ' . $merchant->store_name)

@section('content')
<div class="p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-0 text-dark small">Inventori Stok Fisik Warung</h6>
            <small class="text-muted" style="font-size: 0.72rem;">{{ $merchant->store_name }}</small>
        </div>
        <a href="{{ route('merchant.procurement') }}" class="btn btn-sm btn-success py-1 px-2" style="font-size: 0.75rem;">
            <i class="bi bi-plus-lg me-1"></i> Kulakan Stok
        </a>
    </div>

    @if($inventories->isEmpty())
        <div class="p-4 rounded-3 border bg-white text-center text-muted">
            <i class="bi bi-boxes fs-2 d-block mb-1 opacity-50"></i>
            <span class="small">Belum ada inventori produk terdaftar di toko ini.</span>
        </div>
    @else
        <div class="d-flex flex-column gap-2 mb-4">
            @foreach($inventories as $inv)
                @php
                    $p = $inv->product;
                    $isLow = $inv->stock <= 10;
                @endphp
                <div class="card border shadow-sm {{ $isLow ? 'border-warning' : '' }}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-light text-secondary border small" style="font-size: 0.65rem;">
                                    {{ $p->category?->name }} • SKU: {{ $p->sku }}
                                </span>
                                <h6 class="fw-bold text-dark mb-0 mt-1 small">{{ $p->name }}</h6>
                                <span class="text-muted" style="font-size: 0.72rem;">Satuan: {{ $p->unit_name }}</span>
                            </div>
                            
                            @if($isLow)
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">
                                    Stok Menipis: {{ $inv->stock }}
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                    Stok: {{ $inv->stock }}
                                </span>
                            @endif
                        </div>

                        <!-- Form Edit Harga Eceran Toko & Ketersediaan -->
                        <form action="{{ route('merchant.updateInventory', $inv->id) }}" method="POST" class="mt-2 pt-2 border-top">
                            @csrf
                            <div class="row g-2 align-items-center">
                                <div class="col-7">
                                    <label class="form-label mb-1 text-muted small" style="font-size: 0.68rem;">
                                        Harga Jual Eceran Toko (Rp):
                                    </label>
                                    <input type="number" name="custom_price" class="form-control form-control-sm" 
                                           value="{{ $inv->custom_price ?? $p->base_price }}" step="100">
                                    <small class="text-muted" style="font-size: 0.65rem;">Acuan: Rp {{ number_format($p->base_price, 0, ',', '.') }}</small>
                                </div>
                                <div class="col-5 text-end">
                                    <div class="form-check form-switch mb-1 d-inline-block">
                                        <input class="form-check-input" type="checkbox" name="is_available" id="avail_{{ $inv->id }}" 
                                               value="1" {{ $inv->is_available ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="avail_{{ $inv->id }}" style="font-size: 0.7rem;">Aktif</label>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100 py-1" style="font-size: 0.7rem;">
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
