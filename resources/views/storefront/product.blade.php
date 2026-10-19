@extends('layouts.storefront')

@section('title', $product->name . ' - Mak Ethek')

@section('header_extra')
    <div class="px-1 pt-2 d-flex align-items-center gap-2">
        <a href="{{ route('storefront.index') }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-decoration-none">
            <i class="icofont-rounded-left"></i> Kembali
        </a>
        <div class="fw-bold text-truncate small flex-grow-1" style="max-width: 320px;">
            {{ $product->name }}
        </div>
    </div>
@endsection

@section('content')
    @php
        $stock = $inventory ? $inventory->stock : 0;
        $price = $inventory ? $inventory->effective_price : (float) $product->base_price;
        $isAvailable = $inventory ? ($inventory->is_available && $stock > 0) : false;
        $imgIndex = ($product->id % 12) + 1;
        $displayImg = $product->image ? asset($product->image) : "/grofar/img/listing/v{$imgIndex}.jpg";
    @endphp

    <div class="osahan-product">
        <!-- Gambar Produk Banner -->
        <div class="bg-white p-4 text-center border-bottom position-relative">
            <span class="badge bg-dark bg-opacity-75 text-white position-absolute top-0 start-0 m-3 px-2 py-1 small">
                {{ $product->unit_name }}
            </span>

            @if($isAvailable)
                <span class="badge bg-success position-absolute top-0 end-0 m-3 px-2 py-1 small">
                    <i class="icofont-check-circled me-1"></i> Stok: {{ $stock }}
                </span>
            @else
                <span class="badge bg-danger position-absolute top-0 end-0 m-3 px-2 py-1 small">
                    Habis
                </span>
            @endif

            <img src="{{ $displayImg }}" class="img-fluid rounded shadow-sm" style="max-height: 220px; object-fit: contain;" alt="{{ $product->name }}">
        </div>

        <div class="p-3 bg-white border-bottom">
            <!-- Kategori & SKU -->
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="badge bg-success-subtle text-success border border-success px-2 py-1 small">
                    {{ $product->category?->name ?? 'Kebutuhan Pokok' }}
                </span>
                <span class="text-muted small">SKU: {{ $product->sku }}</span>
            </div>

            <!-- Nama & Harga -->
            <h5 class="fw-bold text-dark mt-2 mb-1">{{ $product->name }}</h5>
            <div class="d-flex align-items-baseline gap-2 mb-2">
                <h4 class="fw-bold text-success mb-0">Rp {{ number_format($price, 0, ',', '.') }}</h4>
                <span class="text-muted">/ {{ $product->unit_name }}</span>
            </div>

            <!-- Jaminan Koperasi -->
            <div class="p-2 rounded bg-light border d-flex align-items-center gap-2" style="font-size: 0.75rem;">
                <i class="icofont-badge text-warning fs-5"></i>
                <div class="text-secondary">
                    Distribusi resmi holding <strong>Koperasi Jawa Timur</strong>. Terjamin keaslian dan timbangan pas.
                </div>
            </div>
        </div>

        <!-- Info Toko Mitra Pemenuhan -->
        @if($merchant)
            <div class="p-3 border-bottom bg-white">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="icofont-shop fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark small">{{ $merchant->store_name }}</div>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <i class="icofont-location-pin text-danger"></i> {{ $merchant->address }} ({{ $merchant->territory?->name }})
                        </div>
                    </div>
                    <div class="ms-auto text-end">
                        <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">
                            Radius {{ $merchant->delivery_radius_km }} km
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Deskripsi Produk -->
        <div class="p-3 bg-white border-bottom mb-3">
            <h6 class="fw-bold text-dark mb-2">Deskripsi Produk</h6>
            <p class="text-secondary small mb-0" style="line-height: 1.6;">
                {{ $product->description ?? 'Produk sembako kebutuhan harian dari rantai pasok Koperasi Jawa Timur. Mutu teruji, aman dikonsumsi seluruh keluarga.' }}
            </p>
        </div>

        <!-- Form Tambah ke Keranjang -->
        <div class="p-3 bg-white border rounded mx-3 mb-4 shadow-sm">
            @if($isAvailable)
                <form action="{{ route('storefront.cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="fw-bold text-dark small">Pilih Jumlah ({{ $product->unit_name }}):</span>
                        <div class="input-group input-spinner cart-items-number" style="width: 130px;">
                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="decrementQty()"> − </button>
                            <input type="number" id="detailQty" name="quantity" class="form-control form-control-sm text-center fw-bold" 
                                   value="1" min="1" max="{{ $stock }}" style="border-left: 0; border-right: 0;">
                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="incrementQty({{ $stock }})"> + </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success fw-bold py-2 w-100 shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <i class="icofont-cart"></i> Tambah ke Keranjang Belanja
                    </button>
                </form>
            @else
                <div class="text-center py-2">
                    <p class="text-danger small mb-2"><i class="icofont-close-circled"></i> Maaf, produk ini sedang kosong di toko mitra wilayah Anda.</p>
                    <button type="button" class="btn btn-secondary fw-semibold py-2 w-100" disabled>
                        Stok Tidak Tersedia
                    </button>
                </div>
            @endif

            <div class="mt-2 text-center">
                <a href="{{ route('storefront.index') }}" class="small text-muted text-decoration-none">
                    <i class="icofont-rounded-left"></i> Kembali ke Katalog Produk
                </a>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    function incrementQty(max) {
        var input = document.getElementById('detailQty');
        var val = parseInt(input.value) || 1;
        if (val < max) {
            input.value = val + 1;
        }
    }
    function decrementQty() {
        var input = document.getElementById('detailQty');
        var val = parseInt(input.value) || 1;
        if (val > 1) {
            input.value = val - 1;
        }
    }
</script>
@endsection
